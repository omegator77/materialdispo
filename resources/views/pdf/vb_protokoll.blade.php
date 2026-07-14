<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>VB-Protokoll {{ $production->bezeichnung }}</title>

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
            text-align: left;
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

        .field-grid {
            width: 100%;
            border-collapse: collapse;
        }

        .field-grid td {
            border: none;
            padding: 4px 8px 4px 0;
            width: 25%;
        }

        .field-label {
            display: block;
            font-size: 9px;
            color: #777;
            text-transform: uppercase;
        }

        .field-value {
            font-weight: bold;
        }

        .text-block {
            margin-bottom: 12px;
        }

        .text-block-title {
            font-weight: bold;
            margin-bottom: 3px;
        }

        .text-block-body {
            white-space: pre-line;
        }

        .status-ok {
            color: #15803d;
            font-weight: bold;
        }

        .status-missing {
            color: #b91c1c;
            font-weight: bold;
        }

        .status-na {
            color: #777;
        }

        .photos {
            margin-top: 8px;
        }

        .photos img {
            width: 47%;
            margin: 0 3% 10px 0;
            border: 1px solid #ccc;
        }
    </style>
</head>

<body>

    <h1>VB-Protokoll: {{ $production->bezeichnung }}</h1>

    <div class="print-meta">
        <strong>Gedruckt von:</strong> {{ auth()->user()->name ?? 'Unbekannt' }}<br>
        <strong>Gedruckt am:</strong> {{ now()->format('d.m.Y H:i') }}
    </div>

    {{-- Kopf --}}
    <h2>Allgemein</h2>
    <table class="field-grid">
        <tr>
            <td>
                <span class="field-label">Kunde</span>
                <span class="field-value">{{ $vbProtokoll->kunde ?: '—' }}</span>
            </td>
            <td>
                <span class="field-label">Produktionsort</span>
                <span class="field-value">{{ $vbProtokoll->produktionsort ?: '—' }}</span>
            </td>
            <td>
                <span class="field-label">Produktionszeit</span>
                <span class="field-value">
                    {{ \Carbon\Carbon::parse($production->booking_start)->format('d.m.Y') }}
                    – {{ \Carbon\Carbon::parse($production->booking_end)->format('d.m.Y') }}
                </span>
            </td>
            <td>
                <span class="field-label">Erfasst von</span>
                <span class="field-value">{{ $vbProtokoll->creator?->name ?? '—' }} ({{ $vbProtokoll->created_at->format('d.m.Y') }})</span>
            </td>
        </tr>
    </table>

    {{-- Crew --}}
    @php
        $crewFields = [
            'crew_ul' => 'ÜL', 'crew_bt_sng' => 'BT', 'crew_ti' => 'TI',
            'crew_sng' => 'SNG', 'crew_bt_dl' => 'BT DL', 'crew_tt' => 'TT',
            'crew_tl' => 'TL', 'crew_ba' => 'BA', 'crew_ta' => 'TA',
            'crew_kabelhilfen' => 'Kabelhilfen', 'crew_kamera' => 'Kamera', 'crew_evs' => 'EVS',
        ];
        $crewChunks = collect($crewFields)->chunk(4);
    @endphp

    <h2>Crew</h2>
    <table class="field-grid">
        @foreach($crewChunks as $chunk)
        <tr>
            @foreach($chunk as $field => $label)
            <td>
                <span class="field-label">{{ $label }}</span>
                <span class="field-value">{{ $vbProtokoll->{$field} ?: '—' }}</span>
            </td>
            @endforeach
        </tr>
        @endforeach
    </table>

    {{-- Anforderungen --}}
    @if($vbProtokoll->anforderungen->count())
    @if($showAbgleich)
    <h2>Anforderungen – Abgleich VB-Protokoll → Packliste → Gepackt</h2>
    <table>
        <thead>
            <tr>
                <th style="width: 32%;">Kategorie</th>
                <th style="width: 12%;">Benötigt</th>
                <th style="width: 12%;">Packliste</th>
                <th style="width: 12%;">Gepackt</th>
                <th style="width: 14%;">Status</th>
                <th style="width: 18%;">Notiz</th>
            </tr>
        </thead>
        <tbody>
            @foreach($vbProtokoll->abgleichMitPackstatus() as $row)
            <tr>
                <td>{{ $row['label'] }}</td>
                <td>{{ $row['benoetigt'] ?? '—' }}</td>
                <td>{{ $row['zugeordnet'] ?? '—' }}</td>
                <td>{{ $row['gepackt'] ?? '—' }}</td>
                <td>
                    @if(is_null($row['erfuellt']))
                        <span class="status-na">—</span>
                    @elseif($row['erfuellt'])
                        <span class="status-ok">✓ erfüllt</span>
                    @else
                        <span class="status-missing">⚠ fehlt {{ $row['benoetigt'] - $row['gepackt'] }}</span>
                    @endif
                </td>
                <td>{{ $row['notiz'] ?: '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <h2>Anforderungen</h2>
    <table>
        <thead>
            <tr>
                <th style="width: 55%;">Kategorie</th>
                <th style="width: 15%;">Anzahl</th>
                <th style="width: 30%;">Notiz</th>
            </tr>
        </thead>
        <tbody>
            @foreach($vbProtokoll->abgleich() as $row)
            <tr>
                <td>{{ $row['label'] }}</td>
                <td>{{ $row['benoetigt'] ?? '—' }}</td>
                <td>{{ $row['notiz'] ?: '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
    @endif

    {{-- Besonderheiten / Kabelwege --}}
    @if($vbProtokoll->besonderheiten || $vbProtokoll->kabelwege)
    <h2>Besonderheiten</h2>
    @if($vbProtokoll->besonderheiten)
    <div class="text-block">
        <div class="text-block-title">Besonderheiten</div>
        <div class="text-block-body">{{ $vbProtokoll->besonderheiten }}</div>
    </div>
    @endif
    @if($vbProtokoll->kabelwege)
    <div class="text-block">
        <div class="text-block-title">Kabelwege, Länge, Überbauten, Besonderheiten</div>
        <div class="text-block-body">{{ $vbProtokoll->kabelwege }}</div>
    </div>
    @endif
    @endif

    {{-- Audio / Technik --}}
    @php
        $textBlocks = [
            'audio_mic' => 'Mic Anzahl und Art',
            'audio_inear' => 'In Ear Sender/Empfänger',
            'audio_kommplatz' => 'Kommplatz/Sprechstellen/4-Draht',
            'isdn_funk' => 'ISDN/SIP/Funk',
            'maz_evs_usb' => 'MAZ/EVS/USB',
            'monitore' => 'Monitore',
            'sonstiges' => 'Sonstiges',
            'zeitplan' => 'Zeitplan',
        ];
        $filledBlocks = collect($textBlocks)->filter(fn ($label, $field) => !empty($vbProtokoll->{$field}));
    @endphp

    @if($filledBlocks->count())
    <h2>Audio / Technik</h2>
    @foreach($filledBlocks as $field => $label)
    <div class="text-block">
        <div class="text-block-title">{{ $label }}</div>
        <div class="text-block-body">{{ $vbProtokoll->{$field} }}</div>
    </div>
    @endforeach
    @endif

    {{-- Fotos --}}
    @if($fotoPaths->count())
    <h2>Fotos / Lagepläne</h2>
    <div class="photos">
        @foreach($fotoPaths as $path)
            <img src="{{ $path }}">
        @endforeach
    </div>
    @endif

</body>

</html>
