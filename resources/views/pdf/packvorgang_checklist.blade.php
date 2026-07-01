<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Packvorgang {{ $production->bezeichnung }}</title>

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
            vertical-align: top;
        }

        .small {
            font-size: 10px;
            color: #555;
        }

        .item-name {
            font-weight: bold;
        }

        .checkbox {
            font-size: 16px;
            text-align: center;
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

    <h1>Packvorgang-Checkliste: {{ $production->bezeichnung }}</h1>

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

    @if($cameraGroups->isNotEmpty())
        <h3>Kamerazüge</h3>

        @foreach($cameraGroups as $configId => $configEntries)
            <table>
                <thead>
                    <tr>
                        <th style="width: 8%;">Im Rüstwagen</th>
                        <th style="width: 20%;">Rolle</th>
                        <th style="width: 42%;">Gerät</th>
                        <th style="width: 30%;">Notiz</th>
                    </tr>
                </thead>

                <tbody>
                    <tr>
                        <td colspan="4" class="small"><strong>Kamera {{ $configEntries->first()['cam_number'] ?? '?' }}</strong></td>
                    </tr>
                    @foreach ($configEntries as $entry)
                        <tr>
                            <td class="checkbox">{{ $packedIds->contains($entry['item']->id) ? '☑' : '☐' }}</td>
                            <td>{{ $entry['role'] }}</td>
                            <td>
                                <span class="item-name">{{ $itemLabel($entry['item']) }}</span>
                            </td>
                            <td>
                                @if(!empty($entry['notes']))
                                    {{ $entry['notes'] }}
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

    @forelse($groupedEinzelEntries as $unitName => $unitEntries)
        <h3>{{ $unitName }}</h3>

        <table>
            <thead>
                <tr>
                    <th style="width: 8%;">Im Rüstwagen</th>
                    <th style="width: 62%;">Gerät</th>
                    <th style="width: 30%;">Notiz</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($unitEntries as $entry)
                    <tr>
                        <td class="checkbox">{{ $packedIds->contains($entry['item']->id) ? '☑' : '☐' }}</td>
                        <td>
                            <span class="item-name">{{ $itemLabel($entry['item']) }}</span>
                        </td>
                        <td>
                            @if(!empty($entry['notes']))
                                {{ $entry['notes'] }}
                            @else
                                <span class="empty">—</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @empty
        @if($cameraGroups->isEmpty())
            <p class="empty">Keine Geräte in dieser Packliste.</p>
        @endif
    @endforelse

</body>

</html>
