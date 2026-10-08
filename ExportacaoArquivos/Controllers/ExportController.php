<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    protected function resolveConfig(Request $request): array
    {
        $exports = config('exports.presets', []);

        $modelKey = $request->validate([
            'model' => ['sometimes', 'string', Rule::in(array_keys($exports))],
        ])['model'] ?? 'documentos';

        $export = $exports[$modelKey];
        $ability = $export['ability'] ?? null;

        if ($ability !== null) {
            Gate::authorize($ability);
        }

        unset($export['ability']);

        $rules = [
            'columns' => ['sometimes', 'array', 'min:1'],
            'columns.*' => ['string', Rule::in($export['columns'])],
            'search_columns' => ['sometimes', 'array'],
            'search_columns.*' => ['string', Rule::in($export['search_columns'])],
            'date_column' => ['sometimes', 'string', Rule::in($export['date_columns'])],
            'order_by' => ['sometimes', 'string', Rule::in($export['order_columns'])],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:1000'],
            'busca' => ['sometimes', 'nullable', 'string', 'max:255'],
            'data_inicio' => ['sometimes', 'nullable', 'date'],
            'data_fim' => ['sometimes', 'nullable', 'date', 'after_or_equal:data_inicio'],
            'filename' => ['sometimes', 'string', 'max:80', 'regex:/\A[A-Za-z0-9_-]+\z/'],
            'view' => ['prohibited'],
        ];

        foreach ($export['filters'] as $parameter => $filter) {
            $rules[$parameter] = array_merge(['sometimes'], $filter['rules']);
        }

        $validated = $request->validate($rules);

        return array_merge($export, [
            'columns' => $validated['columns'] ?? $export['default_columns'],
            'search_columns' => $validated['search_columns'] ?? $export['search_columns'],
            'date_column' => $validated['date_column'] ?? $export['date_column'],
            'order_by' => $validated['order_by'] ?? $export['order_by'],
            'limit' => (int) ($validated['limit'] ?? 1000),
            'filename' => $validated['filename'] ?? $export['filename'],
        ]);
    }

    protected function query(Request $request, array $config): Builder
    {
        $modelClass = $config['model'];
        $model = new $modelClass;
        $query = $model->newQuery()->select($config['columns']);

        if ($request->filled('busca') && $config['search_columns'] !== []) {
            $query->where(function (Builder $query) use ($request, $config) {
                foreach ($config['search_columns'] as $column) {
                    $query->orWhere($column, 'like', '%'.$request->string('busca').'%');
                }
            });
        }

        if ($request->filled('data_inicio')) {
            $query->whereDate($config['date_column'], '>=', $request->input('data_inicio'));
        }

        if ($request->filled('data_fim')) {
            $query->whereDate($config['date_column'], '<=', $request->input('data_fim'));
        }

        foreach ($config['filters'] as $parameter => $filter) {
            if ($request->filled($parameter)) {
                $query->where($filter['column'], $filter['operator'] ?? '=', $request->input($parameter));
            }
        }

        $query->orderBy($config['order_by']);

        if ($config['order_by'] !== $model->getKeyName()) {
            $query->orderBy($model->getKeyName());
        }

        return $query;
    }

    public function pdf(Request $request)
    {
        $config = $this->resolveConfig($request);
        $modelClass = $config['model'];
        $dados = $this->query($request, $config)->limit($config['limit'])->get();

        return Pdf::loadView($config['view'], [
            'dados' => $dados,
            'columns' => $config['columns'],
            'modelClass' => $modelClass,
            'modelName' => class_basename($modelClass),
        ])
            ->setPaper('a4', 'landscape')
            ->stream($config['filename'].'.pdf');
    }

    public function csv(Request $request): StreamedResponse
    {
        $config = $this->resolveConfig($request);
        $filename = $config['filename'].'.csv';

        return response()->stream(function () use ($request, $config) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $this->resolveHeaders($config['columns']), ';');

            $exported = 0;
            $this->query($request, $config)->chunk(500, function ($rows) use ($out, $config, &$exported) {
                foreach ($rows as $row) {
                    if ($exported >= $config['limit']) {
                        return false;
                    }

                    $values = array_map(
                        fn (string $column) => $this->formatValue(data_get($row, $column)),
                        $config['columns'],
                    );
                    fputcsv($out, $values, ';');
                    $exported++;
                }
            });

            fclose($out);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function xlsx(Request $request): BinaryFileResponse
    {
        $config = $this->resolveConfig($request);
        $filename = $config['filename'].'.xlsx';

        $rows = $this->query($request, $config)->limit($config['limit'])->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $totalCols = count($config['columns']);

        foreach ($this->resolveHeaders($config['columns']) as $i => $header) {
            $sheet->setCellValueExplicit([$i + 1, 1], $header, DataType::TYPE_STRING);
        }
        $sheet->getStyle([1, 1, $totalCols, 1])->getFont()->setBold(true);

        $rowNumber = 2;
        foreach ($rows as $row) {
            foreach ($config['columns'] as $i => $column) {
                $value = $this->formatValue(data_get($row, $column), escapeFormulas: false);
                $cell = [$i + 1, $rowNumber];

                if (is_int($value) || is_float($value)) {
                    $sheet->setCellValueExplicit($cell, $value, DataType::TYPE_NUMERIC);
                } else {
                    $sheet->setCellValueExplicit($cell, (string) $value, DataType::TYPE_STRING);
                }
            }
            $rowNumber++;
        }

        for ($c = 1; $c <= $totalCols; $c++) {
            $sheet->getColumnDimensionByColumn($c)->setAutoSize(true);
        }

        $path = tempnam(sys_get_temp_dir(), 'xlsx_');
        (new Xlsx($spreadsheet))->save($path);

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    protected function resolveHeaders(array $columns): array
    {
        return array_map(fn (string $column) => Str::of($column)
            ->replace(['.', '_'], ' ')
            ->title()
            ->toString(), $columns);
    }

    protected function formatValue(mixed $value, bool $escapeFormulas = true): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('d/m/Y H:i');
        }

        if (is_bool($value)) {
            return $value ? 'Sim' : 'Não';
        }

        if ($value === null) {
            return '';
        }

        if ($escapeFormulas && is_string($value) && preg_match('/^[\s\x00-\x1F]*[=+\-@]/u', $value)) {
            return "'".$value;
        }

        return $value;
    }
}
