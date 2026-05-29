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
            <em>Objektiv:</em> {{ $config->lensItem->bezeichnung ?? '/' }}
                            @isset($config->lensItem->nummer)
                            <span class="font-bold"> Nr.{{ $config->lensItem->nummer }}</span>
                            @endisset
            <br>
            <em>Adapter:</em> {{ $config->adapterItem->bezeichnung ?? '/' }}
            @isset($config->adapterItem->nummer)
            <span class="font-bold"> Nr.{{ $config->adapterItem->nummer }}</span>
            @endisset
            <br>
            <em>Stativkopf:</em> {{ $config->headItem->bezeichnung ?? '/' }}
            @isset($config->headItem->nummer)
            <span class="font-bold"> Nr.{{ $config->headItem->nummer }}</span>                                                        
            @endisset
            <br>    
            <em>Stativ:</em> {{ $config->tripod ?? '/' }}<br>
            <em>Position:</em> {{ $config->cam_position ?? '/' }}</td>
            <td>ToDo: Notes einbinden</td>

            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>


<<!--  @foreach ($cameraConfigs as $config)
                    <tr class="even:bg-green-200">
                        <td class="text-left w-12 pl-4">
                            <a href="{{ route('productions.pdf', $config->production->id) }}" class="btn btn-primary" title="PDF Exportieren">
                                <i class="text-left text-red-500 fas fa-file-pdf"></i>
                            </a>
                        </td>
                        <td class="text-left pl-4">{{ $config->production->bezeichnung }}</td>
                        <td class="text-left pl-4">
                            <strong>Kamera:</strong> {{ $config->item->bezeichnung ?? '/' }}
                            @isset($config->item->nummer)
                            <span class="font-bold">{{ $config->item->nummer }}</span>
                            @endisset
                            <br>

                            <strong>Objektiv:</strong> {{ $config->lensItem->bezeichnung }}
                            @isset($config->lensItem->nummer)
                            <span class="font-bold">{{ $config->lensItem->nummer }}</span>
                            @endisset
                            <br>

                            <strong>Largelens-Adapter:</strong> {{ $config->adapterItem->bezeichnung ?? '/' }}
                            @isset($config->adapterItem->nummer)
                            <span class="font-bold">{{ $config->adapterItem->nummer }}</span>
                            @endisset
                            <br>

                            <strong>Stativkopf:</strong> {{ $config->headItem->bezeichnung ?? '/' }}
                            @isset($config->headItem->nummer)
                            <span class="font-bold">{{ $config->headItem->nummer }}</span>                                                        
                            @endisset
                            <br>

                            <strong>Stativ:</strong> {{ $config->tripodItem->bezeichnung ?? '/' }}
                            @isset($config->tripodItem->nummer)
                            <span class="font-bold">{{ $config->tripodItem->nummer }}</span>                                                        
                            @endisset
                            <br>

                            <strong>Position:</strong> {{ $config->cam_position ?? '/' }}
                        </td>
                        <td class="text-left pl-4">{{ $config->item->unit->bezeichnung ?? '/' }}</td>
                    </tr>
                    @endforeach -->

                    