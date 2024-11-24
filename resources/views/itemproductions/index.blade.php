<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Zuordnung</title>
    @vite('resources/css/app.css')
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
    <x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Packliste') }}
        </h2>
    </x-slot>
       

        <!-- Filterformular -->
         <div class="form" >
        <form method="GET" action="{{ route('itemprods') }}">
            <div class="form-group">
                <!-- Filter nach Produktion -->
                <label for="productionFilter">Produktion:</label>
                <select class="rounded-md" id="productionFilter" name="production_id" onchange="this.form.submit()">
                    <option value="">Alle Produktionen</option>
                    @foreach($allProductions as $production)
                        <option value="{{ $production->id }}" 
                            {{ ($filters['production_id'] ?? '') == $production->id ? 'selected' : '' }}>
                            {{ $production->bezeichnung }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <!-- Filter nach Gruppe -->
                <label for="unitFilter">Gruppe:</label>
                <select class="rounded-md"  id="unitFilter" name="unit_id" onchange="this.form.submit()">
                    <option value="">Alle Gruppen</option>
                    @foreach($allUnits as $unit)
                        <option value="{{ $unit->id }}" 
                            {{ ($filters['unit_id'] ?? '') == $unit->id ? 'selected' : '' }}>
                            {{ $unit->bezeichnung }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <!-- Filter nach Gerät -->
                <label for="itemFilter">Gerät:</label>
                <select class="rounded-md" id="itemFilter" name="item_id" onchange="this.form.submit()">
                    <option value="">Alle Geräte</option>
                    @foreach($allItems as $item)
                        <option value="{{ $item->id }}" 
                            {{ ($filters['item_id'] ?? '') == $item->id ? 'selected' : '' }}>
                            {{ $item->bezeichnung }}
                        </option>
                    @endforeach
                </select>
            </div>
        </form><form method="GET" action="{{ route('itemprods') }}">
        </div>


        <!-- Gefilterte Ergebnisse -->
        <table>
            <thead>
                <tr>
                {{--     <th>Produktions ID</th>   --}}
                    <th>Produktion</th>
                {{--     <th>Item ID</th>   --}}
                    <th>Gerät</th>
                    <th>Beschreibung</th>
                    <th>Gerätegruppe</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($itemproductions as $itemproduction)
                    <tr>
                {{--         <td>{{ $itemproduction->production_id ?? '/' }}</td>   --}}
                        <td>{{ $itemproduction->production->bezeichnung ?? '/' }}</td>
                {{--         <td>{{ $itemproduction->item_id ?? '/' }}</td>    --}}
                        <td>{{ $itemproduction->item->bezeichnung ?? '/' }}</td>
                        <td>{{ $itemproduction->item->description}}</td>
                        <td>{{ $itemproduction->item->unit->bezeichnung ?? '/' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-app-layout>
</body>
</html>
