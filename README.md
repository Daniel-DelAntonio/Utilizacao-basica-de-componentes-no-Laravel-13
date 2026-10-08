# Guia de Implementação e Testes: Usuários e Exportações

Este documento orienta como implementar e testar em qualquer projeto Laravel:
1. **Listagem e Formulário de Usuários** (CRUD completo com paginação e validações).
2. **Exportação Modular para CSV, PDF e XLSX (Excel)**.

---

## 1. Listagem e Formulário

### 1.1. Migration (`database/migrations/...`)
O modelo de usuário utiliza os campos padrões do Laravel mais os campos adicionais: `login`, `function_name` e `system_unit_id`.

Se você estiver em um projeto novo ou adicionando a uma tabela existente:

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('login')->unique()->after('name');
            $table->string('function_name', 256)->nullable()->after('email');
            $table->integer('system_unit_id')->nullable()->after('function_name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['login', 'function_name', 'system_unit_id']);
        });
    }
};
```

Execute as migrations:
```bash
php artisan migrate
```

---

### 1.2. Model `User` (`app/Models/User.php`)
Garanta que os novos campos estejam liberados para atribuição em massa (`$fillable`):

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'login',
        'email',
        'function_name',
        'system_unit_id',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];
}
```

---

### 1.3. Rotas (`routes/web.php`)
Defina as rotas do recurso de usuários:

```php
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', [UserController::class, 'index'])->name('usuarios.index');
Route::resource('users', UserController::class);
```

---

### 1.4. Controller `UserController` (`app/Http/Controllers/UserController.php`)
Controlador responsável pelas ações de listagem (com paginação e filtros), exibição de formulário, persistência, atualização e exclusão:

```php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'busca' => ['nullable', 'string', 'max:255'],
            'function_name' => ['nullable', Rule::in(['Usuario', 'Administrador', 'Programador'])],
        ]);

        $usuario = User::query()
            ->when($filters['busca'] ?? null, function ($query, $busca) {
                $query->where(function ($q) use ($busca) {
                    $q->where('name', 'like', "%{$busca}%")
                      ->orWhere('login', 'like', "%{$busca}%")
                      ->orWhere('email', 'like', "%{$busca}%");
                });
            })
            ->when($filters['function_name'] ?? null, fn ($query, $fn) => $query->where('function_name', $fn))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('usuario.index', compact('usuario'));
    }

    public function create()
    {
        return view('usuario.formUsuario');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'login' => ['required', 'string', 'max:255', 'unique:users,login'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'function_name' => ['required', 'string', Rule::in(['Usuario', 'Administrador', 'Programador'])],
            'system_unit_id' => ['nullable', 'integer', 'min:1'],
            'password' => ['required', 'string', 'min:3', 'confirmed'],
        ]);

        User::create($data);

        return redirect()->route('users.index')->with('success', 'Usuário criado com sucesso.');
    }

    public function edit(User $user)
    {
        return view('usuario.formUsuario', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'login' => ['required', 'string', 'max:255', Rule::unique('users', 'login')->ignore($user->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'function_name' => ['required', 'string', Rule::in(['Usuario', 'Administrador', 'Programador'])],
            'system_unit_id' => ['nullable', 'integer', 'min:1'],
            'password' => ['nullable', 'string', 'min:3', 'confirmed'],
        ]);

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('users.index')->with('success', 'Usuário atualizado com sucesso.');
    }

    public function destroy(User $user)
    {
        $user->delete();

        return redirect()->route('users.index')->with('success', 'Usuário excluído com sucesso.');
    }
}
```

---

### 1.5. Views Blade

#### Listagem (`resources/views/usuario/index.blade.php`)
Apresenta a listagem paginada, links para criar/editar/excluir e os gatilhos de exportação:

```html
@extends('layouts.app')

@section('content')
<div class="bg-white rounded-md border border-slate-200 overflow-hidden">
    {{-- Barra Superior / Ações --}}
    <div class="px-4 py-3 flex items-center justify-end gap-3 bg-slate-900 text-white">
        <a href="{{ route('users.create') }}" class="px-3 py-2 bg-slate-700 hover:bg-slate-600 rounded text-sm">
            Novo Usuário
        </a>

        {{-- Dropdown de Exportação --}}
        <details class="relative">
            <summary class="cursor-pointer px-3 py-2 bg-slate-700 hover:bg-slate-600 rounded text-sm">
                Exportar
            </summary>
            <div class="absolute right-0 mt-1 w-32 bg-white border border-slate-200 rounded shadow-lg py-1 z-20 text-slate-700">
                <a href="{{ route('exports.csv', array_merge(['model' => 'usuarios'], request()->only(['busca', 'function_name']))) }}" class="block px-3 py-1.5 text-sm hover:bg-slate-100">CSV</a>
                <a href="{{ route('exports.pdf', array_merge(['model' => 'usuarios'], request()->only(['busca', 'function_name']))) }}" class="block px-3 py-1.5 text-sm hover:bg-slate-100">PDF</a>
                <a href="{{ route('exports.xlsx', array_merge(['model' => 'usuarios'], request()->only(['busca', 'function_name']))) }}" class="block px-3 py-1.5 text-sm hover:bg-slate-100">Excel (XLSX)</a>
            </div>
        </details>
    </div>

    {{-- Tabela de Registros --}}
    <table class="w-full text-sm">
        <thead class="bg-slate-100 text-slate-700 border-b">
            <tr>
                <th class="px-4 py-3 text-left">Ações</th>
                <th class="px-4 py-3 text-left">Nome</th>
                <th class="px-4 py-3 text-left">Login</th>
                <th class="px-4 py-3 text-left">E-mail</th>
                <th class="px-4 py-3 text-left">Função</th>
                <th class="px-4 py-3 text-left">Unidade</th>
            </tr>
        </thead>
        <tbody>
            @forelse($usuario as $user)
            <tr class="border-b hover:bg-slate-50">
                <td class="px-4 py-3 flex gap-2">
                    <a href="{{ route('users.edit', $user->id) }}" class="text-blue-600 hover:underline">Editar</a>
                    <form method="POST" action="{{ route('users.destroy', $user->id) }}" onsubmit="return confirm('Deseja excluir?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-red-600 hover:underline">Excluir</button>
                    </form>
                </td>
                <td class="px-4 py-3">{{ $user->name }}</td>
                <td class="px-4 py-3">{{ $user->login }}</td>
                <td class="px-4 py-3">{{ $user->email }}</td>
                <td class="px-4 py-3">{{ $user->function_name ?? '-' }}</td>
                <td class="px-4 py-3">{{ $user->system_unit_id ?? '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-4 py-8 text-center text-slate-500">Nenhum usuário encontrado.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Paginação --}}
    <div class="p-4 bg-slate-50 border-t">
        {{ $usuario->links() }}
    </div>
</div>
@endsection
```

#### Formulário Unificado (`resources/views/usuario/formUsuario.blade.php`)
Formulário reutilizado para criação e edição (com tratamento de senha opcional na edição):

```html
@extends('layouts.app')

@section('content')
<div class="bg-white rounded-md border border-slate-200 p-6">
    <h2 class="text-lg font-bold mb-4">{{ isset($user) ? 'Editar Usuário' : 'Novo Usuário' }}</h2>

    <form id="formUsuario" action="{{ isset($user) ? route('users.update', $user) : route('users.store') }}" method="POST">
        @csrf
        @isset($user)
            @method('PUT')
        @endisset

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="name" class="block text-sm font-medium">Nome</label>
                <input id="name" name="name" type="text" value="{{ old('name', $user->name ?? '') }}" required class="w-full border rounded p-2">
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="login" class="block text-sm font-medium">Login</label>
                <input id="login" name="login" type="text" value="{{ old('login', $user->login ?? '') }}" required class="w-full border rounded p-2">
                @error('login') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-medium">E-mail</label>
                <input id="email" name="email" type="email" value="{{ old('email', $user->email ?? '') }}" required class="w-full border rounded p-2">
                @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="function_name" class="block text-sm font-medium">Função</label>
                <select id="function_name" name="function_name" required class="w-full border rounded p-2">
                    <option value="">Selecione</option>
                    <option value="Usuario" @selected(old('function_name', $user->function_name ?? '') === 'Usuario')>Usuário</option>
                    <option value="Administrador" @selected(old('function_name', $user->function_name ?? '') === 'Administrador')>Administrador</option>
                    <option value="Programador" @selected(old('function_name', $user->function_name ?? '') === 'Programador')>Programador</option>
                </select>
                @error('function_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="system_unit_id" class="block text-sm font-medium">Unidade do Sistema</label>
                <input id="system_unit_id" name="system_unit_id" type="number" min="1" value="{{ old('system_unit_id', $user->system_unit_id ?? '') }}" class="w-full border rounded p-2">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium">{{ isset($user) ? 'Nova Senha (opcional)' : 'Senha' }}</label>
                <input id="password" name="password" type="password" {{ isset($user) ? '' : 'required' }} class="w-full border rounded p-2">
                @error('password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium">Confirmar Senha</label>
                <input id="password_confirmation" name="password_confirmation" type="password" class="w-full border rounded p-2">
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('users.index') }}" class="px-4 py-2 border rounded bg-slate-100 hover:bg-slate-200">Cancelar</a>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Salvar</button>
        </div>
    </form>
</div>
@endsection
```

---

## 2. Exportação de Arquivos para CSV, PDF e XLSX

O sistema utiliza uma abordagem configurável via presets, permitindo reutilizar o mesmo mecanismo para qualquer modelo Eloquent.

### 2.1. Dependências Composer
Para suporte a PDF e XLSX, instale os seguintes pacotes:
```bash
composer require barryvdh/laravel-dompdf phpoffice/phpspreadsheet
```

---

### 2.2. Arquivo de Configuração (`config/exports.php`)
Crie o arquivo `config/exports.php` definindo as regras de exportação para a entidade `usuarios`:

```php
use App\Models\User;

return [
    'presets' => [
        'usuarios' => [
            'model' => User::class,
            'columns' => ['id', 'name', 'login', 'email', 'function_name', 'system_unit_id', 'created_at'],
            'default_columns' => ['id', 'name', 'login', 'function_name'],
            'search_columns' => ['name', 'login', 'email'],
            'filters' => [],
            'date_columns' => ['created_at', 'updated_at'],
            'order_columns' => ['id', 'name', 'created_at'],
            'date_column' => 'created_at',
            'order_by' => 'name',
            'view' => 'exports.generic',
            'filename' => 'usuarios',
            'ability' => null, // Gate/Policy opcional
        ],
    ],
];
```

---

### 2.3. Rotas de Exportação (`routes/web.php`)
Adicione as 3 rotas responsáveis por chamar os métodos de exportação:

```php
use App\Http\Controllers\ExportController;

Route::get('/export/pdf', [ExportController::class, 'pdf'])->name('exports.pdf');
Route::get('/export/csv', [ExportController::class, 'csv'])->name('exports.csv');
Route::get('/export/xlsx', [ExportController::class, 'xlsx'])->name('exports.xlsx');
```

---

### 2.4. Controller `ExportController` (`app/Http/Controllers/ExportController.php`)
Responsável por validar parâmetros, executar a query com os filtros ativos e produzir os arquivos nos formatos solicitados:

```php
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
        ])['model'] ?? 'usuarios';

        $export = $exports[$modelKey];

        if (!empty($export['ability'])) {
            Gate::authorize($export['ability']);
        }

        $validated = $request->validate([
            'columns' => ['sometimes', 'array', 'min:1'],
            'columns.*' => ['string', Rule::in($export['columns'])],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:2000'],
            'busca' => ['sometimes', 'nullable', 'string', 'max:255'],
            'data_inicio' => ['sometimes', 'nullable', 'date'],
            'data_fim' => ['sometimes', 'nullable', 'date', 'after_or_equal:data_inicio'],
            'filename' => ['sometimes', 'string', 'max:80', 'regex:/\A[A-Za-z0-9_-]+\z/'],
        ]);

        return array_merge($export, [
            'columns' => $validated['columns'] ?? $export['default_columns'],
            'limit' => (int) ($validated['limit'] ?? 1000),
            'filename' => $validated['filename'] ?? $export['filename'],
        ]);
    }

    protected function query(Request $request, array $config): Builder
    {
        $modelClass = $config['model'];
        $query = (new $modelClass)->newQuery()->select($config['columns']);

        if ($request->filled('busca') && !empty($config['search_columns'])) {
            $query->where(function (Builder $q) use ($request, $config) {
                foreach ($config['search_columns'] as $col) {
                    $q->orWhere($col, 'like', '%' . $request->string('busca') . '%');
                }
            });
        }

        if ($request->filled('data_inicio')) {
            $query->whereDate($config['date_column'], '>=', $request->input('data_inicio'));
        }

        if ($request->filled('data_fim')) {
            $query->whereDate($config['date_column'], '<=', $request->input('data_fim'));
        }

        return $query->orderBy($config['order_by']);
    }

    // Exportação em PDF
    public function pdf(Request $request)
    {
        $config = $this->resolveConfig($request);
        $dados = $this->query($request, $config)->limit($config['limit'])->get();

        return Pdf::loadView($config['view'], [
            'dados' => $dados,
            'columns' => $config['columns'],
            'modelName' => class_basename($config['model']),
        ])
        ->setPaper('a4', 'landscape')
        ->stream($config['filename'] . '.pdf');
    }

    // Exportação em CSV (streaming em memória)
    public function csv(Request $request): StreamedResponse
    {
        $config = $this->resolveConfig($request);
        $filename = $config['filename'] . '.csv';

        return response()->stream(function () use ($request, $config) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM UTF-8 para Excel abrir sem quebra de acentos
            fputcsv($out, $this->resolveHeaders($config['columns']), ';');

            $exported = 0;
            $this->query($request, $config)->chunk(500, function ($rows) use ($out, $config, &$exported) {
                foreach ($rows as $row) {
                    if ($exported >= $config['limit']) {
                        return false;
                    }
                    $values = array_map(fn ($col) => $this->formatValue(data_get($row, $col)), $config['columns']);
                    fputcsv($out, $values, ';');
                    $exported++;
                }
            });

            fclose($out);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    // Exportação em XLSX (Excel nativo com formatação)
    public function xlsx(Request $request): BinaryFileResponse
    {
        $config = $this->resolveConfig($request);
        $filename = $config['filename'] . '.xlsx';
        $rows = $this->query($request, $config)->limit($config['limit'])->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $totalCols = count($config['columns']);

        // Cabeçalho em negrito
        foreach ($this->resolveHeaders($config['columns']) as $i => $header) {
            $sheet->setCellValueExplicit([$i + 1, 1], $header, DataType::TYPE_STRING);
        }
        $sheet->getStyle([1, 1, $totalCols, 1])->getFont()->setBold(true);

        // Preenchimento das linhas
        $rowNumber = 2;
        foreach ($rows as $row) {
            foreach ($config['columns'] as $i => $col) {
                $val = $this->formatValue(data_get($row, $col), escapeFormulas: false);
                $cell = [$i + 1, $rowNumber];

                if (is_int($val) || is_float($val)) {
                    $sheet->setCellValueExplicit($cell, $val, DataType::TYPE_NUMERIC);
                } else {
                    $sheet->setCellValueExplicit($cell, (string) $val, DataType::TYPE_STRING);
                }
            }
            $rowNumber++;
        }

        // Ajuste automático de largura das colunas
        for ($c = 1; $c <= $totalCols; $c++) {
            $sheet->getColumnDimensionByColumn($c)->setAutoSize(true);
        }

        $path = tempnam(sys_get_temp_dir(), 'xlsx_');
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    protected function resolveHeaders(array $columns): array
    {
        return array_map(fn ($col) => Str::of($col)->replace(['.', '_'], ' ')->title()->toString(), $columns);
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
        // Prevenção de CSV Formula Injection (=, +, -, @)
        if ($escapeFormulas && is_string($value) && preg_match('/^[\s\x00-\x1F]*[=+\-@]/u', $value)) {
            return "'" . $value;
        }
        return $value;
    }
}
```

---

### 2.5. View Blade do PDF (`resources/views/exports/generic.blade.php`)
Template HTML renderizado pelo DomPDF para gerar o documento PDF formatado:

```html
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>{{ $modelName ?? 'Relatório' }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10px; color: #222; margin: 15px; }
        h2 { text-align: center; margin-bottom: 15px; font-size: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #c9c9c9; padding: 6px 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .empty { text-align: center; color: #666; padding: 20px; }
    </style>
</head>
<body>
    <h2>Relatório de {{ $modelName ?? 'Registros' }}</h2>

    @if ($dados->isEmpty())
        <div class="empty">Nenhum registro encontrado.</div>
    @else
        <table>
            <thead>
                <tr>
                    @foreach ($columns as $column)
                        <th>{{ ucwords(str_replace(['_', '.'], ' ', $column)) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($dados as $item)
                    <tr>
                        @foreach ($columns as $column)
                            @php
                                $val = data_get($item, $column);
                                if ($val instanceof \DateTimeInterface) {
                                    $val = $val->format('d/m/Y H:i');
                                } elseif (is_bool($val)) {
                                    $val = $val ? 'Sim' : 'Não';
                                } elseif ($val === null) {
                                    $val = '';
                                }
                            @endphp
                            <td>{{ $val }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
```

---

## 3. Como Testar no Outro Projeto

1. **Instalar pacotes**:
   ```bash
   composer require barryvdh/laravel-dompdf phpoffice/phpspreadsheet
   ```
2. **Executar migrações**:
   ```bash
   php artisan migrate
   ```
3. **Criar registros de teste**:
   Crie alguns usuários pelo formulário em `/users/create` ou via `php artisan tinker`:
   ```php
   App\Models\User::factory()->create(['login' => 'admin', 'function_name' => 'Administrador']);
   App\Models\User::factory()->create(['login' => 'dev', 'function_name' => 'Programador']);
   ```
4. **Testar no Navegador**:
   - **Listagem e Ações**: Acesse `http://localhost:8000/users` (ou `/`), teste a paginação, a edição (`/users/{id}/edit`) e a exclusão.
   - **Exportação CSV**: Acesse `http://localhost:8000/export/csv?model=usuarios` (o download de `usuarios.csv` será iniciado imediatamente).
   - **Exportação PDF**: Acesse `http://localhost:8000/export/pdf?model=usuarios` (o PDF abrirá no navegador formatado em A4 paisagem).
   - **Exportação Excel**: Acesse `http://localhost:8000/export/xlsx?model=usuarios` (o download de `usuarios.xlsx` será baixado com formatação de colunas e cabeçalhos em negrito).

#   U t i l i z a c a o - b a s i c a - d e - c o m p o n e n t e s - n o - L a r a v e l - 1 3  
 