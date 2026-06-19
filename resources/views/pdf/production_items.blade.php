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
            margin-bottom: 12px;
            font-size: 12px;
        }

        .print-meta {
            margin-bottom: 18px;
            font-size: 10px;
            color: #555;
        }

        .packlist-notes {
            margin: 14px 0 18px 0;
            padding: 10px;
            border: 1px solid #ccc;
            background: #fff8dc;
        }

        .packlist-notes-title {
            font-weight: bold;
            margin-bottom: 4px;
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
            vertical-align: top;
        }

        .small {
            font-size: 10px;
            color: #555;
        }

        .item-name {
            font-weight: bold;
        }

        .config-line {
            margin-bottom: 3px;
        }

        .note {
            margin-top: 5px;
            padding-top: 4px;
            border-top: 1px dotted #bbb;
        }

        .empty {
            color: #777;
        }
    </style>
</head>

<body>

    @php
        $itemLabel = function ($item) {
            if (! $item) {
                return null;
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

    <div class="print-meta">
        <strong>Gedruckt von:</strong> {{ auth()->user()->name ?? 'Unbekannt' }}<br>
        <strong>Gedruckt am:</strong> {{ now()->format('d.m.Y H:i') }}
    </div>

    @if(!empty($production->packlist_notes))
        <div class="packlist-notes">
            <div class="packlist-notes-title">Packlisten-Notiz</div>
            <div>{!! nl2br(e($production->packlist_notes)) !!}</div>
        </div>
    @endif

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
                            @php
                                $configRows = [
                                    'Kamera' => $config->item ?? null,
                                    'Objektiv' => $config->lensItem ?? null,
                                    'Adapter' => $config->adapterItem ?? null,
                                    'Stativkopf' => $config->headItem ?? null,
                                    'Stativ' => $config->tripodItem ?? null,
                                ];
                            @endphp

                            @foreach($configRows as $labelName => $configItem)
                                @if($configItem)
                                    <div class="config-line">
                                        <span class="small">{{ $labelName }}:</span>
                                        @if($labelName === 'Kamera')
                                            <span class="item-name">{{ $itemLabel($configItem) }}</span>
                                        @else
                                            {{ $itemLabel($configItem) }}
                                        @endif
                                    </div>
                                @endif
                            @endforeach

                            @if(!empty($config->notes))
                                <div class="note">
                                    <span class="small">Notiz:</span><br>
                                    {!! nl2br(e($config->notes)) !!}
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
                                @if(!empty($item->pivot->notes))
                                    {!! nl2br(e($item->pivot->notes)) !!}
                                @else
                                    <span class="empty">—</span>
                                @endif
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