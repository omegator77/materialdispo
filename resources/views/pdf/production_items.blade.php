<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Packliste {{ $production->bezeichnung }}</title>

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

        h2 {
            font-size: 15px;
            margin: 22px 0 8px 0;
            padding: 6px 8px;
            background: #f1f1f1;
            border: 1px solid #ccc;
        }

        h3 {
            font-size: 13px;
            margin: 14px 0 6px 0;
            border-bottom: 1px solid #ccc;
            padding-bottom: 3px;
        }

        .meta {
            margin-bottom: 18px;
            font-size: 12px;
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

        th, td {
            border: 1px solid #ccc;
            padding: 6px;
            vertical-align: top;
        }

        .small {
            font-size: 10px;
            color: #555;
        }

        .item-name {
            font-weight: bold;
        }

        .number {
            font-weight: bold;
        }

        .page-break {
            page-break-before: always;
        }
    </style>
</head>

<body>

@php
    $itemLabel = function ($item) {
        if (! $item) {
            return '—';
        }

        return $item->nummer
            ? $item->bezeichnung . ' (' . $item->nummer . ')'
            : $item->bezeichnung;
    };

    $itemsByUnit = $items->groupBy(fn($item) => $item->unit->bezeichnung ?? 'Ohne Gruppe');
@endphp

<h1>Packliste: {{ $production->bezeichnung }}</h1>

<div class="meta">
    <strong>Zeitraum:</strong>
    {{ $production->booking_start ? \Carbon\Carbon::parse($production->booking_start)->format('d.m.Y') : '—' }}
    bis
    {{ $production->booking_end ? \Carbon\Carbon::parse($production->booking_end)->format('d.m.Y') : '—' }}
</div>

@if($cameraConfigs->count())
    <h2>Kamerazüge</h2>

    <table>
        <thead>
            <tr>
                <th style="width: 18%;">Position</th>
                <th style="width: 82%;">Konfiguration</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($cameraConfigs as $config)
                <tr>
                    <td>
                        <strong>{{ $config->cam_number ?? '—' }}</strong>
                    </td>

                    <td>
                        <div><span class="small">Kamera:</span> <span class="item-name">{{ $itemLabel($config->item ?? null) }}</span></div>
                        <div><span class="small">Objektiv:</span> {{ $itemLabel($config->lensItem ?? null) }}</div>
                        <div><span class="small">Adapter:</span> {{ $itemLabel($config->adapterItem ?? null) }}</div>
                        <div><span class="small">Stativkopf:</span> {{ $itemLabel($config->headItem ?? null) }}</div>
                        <div><span class="small">Stativ:</span> {{ $itemLabel($config->tripodItem ?? null) }}</div>

                        @if(!empty($config->notes))
                            <div style="margin-top: 4px;">
                                <span class="small">Notiz:</span> {{ $config->notes }}
                            </div>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

@if($itemsByUnit->count())
    <h2>Weitere Geräte</h2>

    @foreach($itemsByUnit as $unitName => $unitItems)
        <h3>{{ $unitName }}</h3>

        <table>
            <thead>
                <tr>
                    <th style="width: 70%;">Gerät</th>
                    <th style="width: 30%;">Notizen</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($unitItems as $item)
                    <tr>
                        <td>
                            <span class="item-name">{{ $itemLabel($item) }}</span>

                            @if($item->supplier)
                                <br>
                                <span class="small">Mietmaterial: {{ $item->supplier->bezeichnung }}</span>
                            @endif
                        </td>

                        <td>
                            {{ $item->pivot->notes ?? '—' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach
@endif

@if(!$items->count() && !$cameraConfigs->count())
    <p>Keine Geräte in dieser Packliste.</p>
@endif

</body>
</html>