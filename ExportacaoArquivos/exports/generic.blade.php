<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>{{ $modelName ?? 'Exportação' }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            color: #222;
        }

        h2 {
            margin: 0 0 12px;
            font-size: 18px;
            text-align: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th, td {
            border: 1px solid #c9c9c9;
            padding: 6px 8px;
            text-align: left;
            vertical-align: top;
            word-wrap: break-word;
        }

        th {
            background: #f2f2f2;
            font-weight: bold;
        }

        .empty {
            text-align: center;
            color: #666;
            padding: 20px;
        }
    </style>
</head>
<body>
    <h2>{{ $modelName ?? 'Relatório' }}</h2>

    @if ($dados->isEmpty())
        <div class="empty">Nenhum registro encontrado.</div>
    @else
        <table>
            <thead>
                <tr>
                    @foreach ($columns ?? [] as $column)
                        <th>{{ str_replace(['_', '.'], ' ', $column) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($dados as $item)
                    <tr>
                        @foreach ($columns ?? [] as $column)
                            @php
                                $value = data_get($item, $column);

                                if ($value instanceof \DateTimeInterface) {
                                    $value = $value->format('d/m/Y H:i');
                                } elseif (is_bool($value)) {
                                    $value = $value ? 'Sim' : 'Não';
                                } elseif ($value === null) {
                                    $value = '';
                                }
                            @endphp

                            <td>{{ $value }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
