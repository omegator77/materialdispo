<!DOCTYPE html>
<html>
<head>
    <title>Packliste für Produktion {{ $production->bezeichnung }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            margin: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f4f4f4;
        }
        h1 {
            font-size: 24px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <h1>Packliste für Produktion: {{ $production->bezeichnung }}</h1>
    <p><strong>Zeitraum:</strong> {{ $production->booking_start }} bis {{ $production->booking_end }}</p>

    <table>
        <thead>
            <tr>
                
                <th>Item</th>
               
                <th>Notizen</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $index => $item)
                <tr>
                    
                    <td>{{ $item->bezeichnung }}</td>
                    
                    <td>{{ $item->pivot->notes }}</td>
                </tr>
            @endforeach
            @foreach ($cameraConfigs as $config)
            <tr>
            <td><strong>{{ $config->item->bezeichnung }} {{ $config->item->nummer }}</strong><br>
            <em>Objektiv:</em> {{ $config->lensItem?->bezeichnung ?? '/' }} No. {{ $config->lensItem?->nummer ?? '/' }}<br>
            <em>Adapter:</em>  {{ $config->adapItem?->bezeichnung ?? '/' }} No. {{ $config->adapItem?->nummer ?? '/' }}<br>
            <em>Stativ:</em> {{ $config->tripodItem?->bezeichnung ?? '/' }} No.{{ $config->tripodItem?->nummer ?? '/' }}<br>
            <em>Stativkopf:</em> {{ $config->headItem?->bezeichnung ?? '/' }} No. {{ $config->headItem?->nummer ?? '/' }}<br>
            <em>Kameranummer:</em> {{ $config->cam_number ?? '/' }}<br>
            <em>Position:</em> {{ $config->cam_position ?? '/' }}
            <td>@if(!empty($config->notes))
  <div class="mt-2">
    <strong>Notizen:</strong>
    <div class="whitespace-pre-line">{{ $config->notes }}</div>
    {{-- oder: {!! nl2br(e($config->notes)) !!} --}}
  </div>
@endif</td>

            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
