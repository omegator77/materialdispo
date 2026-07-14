<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Materialliste {{ $mietvorgang->bezeichnung }}</title>

    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11px;
            color: #222;
            margin: 24px;
        }

        h1 {
            font-size: 22px;
            margin: 0 0 4px 0;
        }

        h3 {
            font-size: 13px;
            margin: 14px 0 6px 0;
            border-bottom: 1px solid #ccc;
            padding-bottom: 3px;
        }

        .meta {
            margin-bottom: 12px;
            font-size: 12px;
        }

        .print-meta {
            margin-bottom: 18px;
            font-size: 10px;
            color: #555;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        th {
            background: #f4f4f4;
            font-weight: bold;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 6px;
            vertical-align: middle;
        }

        .small {
            font-size: 10px;
            color: #555;
        }

        .item-name {
            font-weight: bold;
        }

        .checkbox-col {
            width: 24px;
        }

        .checkbox {
            display: block;
            width: 12px;
            height: 12px;
            border: 1px solid #444;
        }

        .empty {
            color: #777;
        }
    </style>
</head>

<body>

    @php
        $itemLabel = function ($item) {
            return $item->nummer
                ? $item->bezeichnung . ' (' . $item->nummer . ')'
                : $item->bezeichnung;
        };
    @endphp

    <h1>Materialliste: {{ $mietvorgang->bezeichnung }}</h1>

    <div class="meta">
        <strong>Vermieter:</strong> {{ $mietvorgang->supplier->bezeichnung ?? '—' }}<br>
        <strong>Zeitraum:</strong>
        {{ $mietvorgang->rent_start ? \Carbon\Carbon::parse($mietvorgang->rent_start)->format('d.m.Y') : '—' }}
        bis
        {{ $mietvorgang->rent_end ? \Carbon\Carbon::parse($mietvorgang->rent_end)->format('d.m.Y') : '—' }}
    </div>

    <div class="print-meta">
        <strong>Gedruckt von:</strong> {{ auth()->user()->name ?? 'Unbekannt' }}<br>
        <strong>Gedruckt am:</strong> {{ now()->format('d.m.Y H:i') }}
    </div>

    @if($itemsByUnit->count())
        @foreach($itemsByUnit as $unitName => $unitItems)
            <h3>{{ $unitName }}</h3>

            <table>
                <thead>
                    <tr>
                        <th class="checkbox-col"></th>
                        <th>Gerät</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($unitItems as $item)
                        <tr>
                            <td class="checkbox-col">
                                <span class="checkbox"></span>
                            </td>
                            <td>
                                <span class="item-name">{{ $itemLabel($item) }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach
    @else
        <p>Keine Geräte zugeordnet.</p>
    @endif

</body>

</html>
