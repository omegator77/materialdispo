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

        th,
        td {
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
    <p><strong>Zeitraum:</strong> {{ \Carbon\Carbon::parse($production->booking_start)->format('d.m.Y') }}
bis
{{ \Carbon\Carbon::parse($production->booking_end)->format('d.m.Y') }}</p>

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

                <td>{{ $item->bezeichnung }} <strong>{{ $item->nummer }}</strong></td>

                <td>{{ $item->pivot->notes }}</td>
            </tr>
            @endforeach
            @foreach ($cameraConfigs as $config)
<tr>
    <td>
        <strong>{{ $config->item->bezeichnung }} {{ $config->item->nummer }}</strong><br>

        <em>Objektiv:</em> {{ $config->lensItem->bezeichnung ?? '/' }}
        @isset($config->lensItem->nummer)
            <strong>Nr. {{ $config->lensItem->nummer }}</strong>
        @endisset
        <br>

        <em>Adapter:</em> {{ $config->adapterItem->bezeichnung ?? '/' }}
        @isset($config->adapterItem->nummer)
            <strong>Nr. {{ $config->adapterItem->nummer }}</strong>
        @endisset
        <br>

        <em>Stativkopf:</em> {{ $config->headItem->bezeichnung ?? '/' }}
        @isset($config->headItem->nummer)
            <strong>Nr. {{ $config->headItem->nummer }}</strong>
        @endisset
        <br>

        <em>Stativ:</em> {{ $config->tripodItem->bezeichnung ?? '/' }}
        @isset($config->tripodItem->nummer)
            <strong>Nr. {{ $config->tripodItem->nummer }}</strong>
        @endisset
        <br>

        <em>Position:</em> {{ $config->cam_number ?? '/' }}
    </td>

    <td>ToDo: Notes einbinden</td>
</tr>
@endforeach
        </tbody>
    </table>
</body>

</html>
