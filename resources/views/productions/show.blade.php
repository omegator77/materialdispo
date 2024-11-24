<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initialscale=1.0">
<meta http-equiv="X-UA-Compatible" content="ie=edge">
<title>Vorlage</title>
<link rel="stylesheet" href="{{ asset('css/style.css') }}">

</head>
<body>
<x-app-layout>
<x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Productions') }}
        </h2>
    </x-slot>

<h1>Produktion: {{ $production->bezeichnung }}</h1>
    <p>Buchungszeitraum: {{$production->booking_start ? \Carbon\Carbon::parse ($production->booking_start)->format('d.m.Y') : '/' }} bis {{$production->booking_end ? \Carbon\Carbon::parse ($production->booking_end)->format('d.m.Y') : '/' }}</p>

    <h2>Verknüpfte Items</h2>
    
    <ul  style="list-style-type: disc; padding-left: 20px;">
        @foreach ($production->items as $item)
            <li style="margin-bottom: 10px;">
                {{ $item->bezeichnung }} ({{ $item->unit->bezeichnung }})
                <form action="{{ route('productions.detachItem', [$production->id, $item->id]) }}" method="POST" style="display:inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" style=" background-color: orange;
    color: white;
    padding: 5px 10px;
    border: none;
    border-radius: 4px;
    font-weight: bold;
    cursor: pointer;">Entfernen</button>
                </form>
            </li>
        @endforeach
    </ul>

    <h2>Verfügbare Items hinzufügen</h2>

    <!-- Gruppenauswahl -->
<form method="GET" action="{{ route('productions.show', $production->id) }}">
    <label for="unit">Gruppe filtern:</label>
    <select name="unit" id="unit" onchange="this.form.submit()">
        <option value="">Alle Gruppen</option>
        @foreach ($allUnits as $unit)
            <option value="{{ $unit->id }}" {{ request('unit') == $unit->id ? 'selected' : '' }}>
                {{ $unit->bezeichnung }}
            </option>
        @endforeach
    </select>
</form>

    <!-- Item-Auswahl -->
    <form action="{{ route('productions.attachItem', $production->id) }}" method="POST">
        @csrf
        <!-- Verstecktes Eingabefeld für die Gruppe -->
    <input type="hidden" name="unit" value="{{ request('unit') }}">
        <select name="item_id">
            @foreach ($availableItems as $item)
                <option value="{{ $item->id }}">{{ $item->bezeichnung }} ({{ $item->unit->bezeichnung }})</option>
            @endforeach
        </select>
        <button type="submit" style=" background-color: orange;
    color: white;
    padding: 5px 10px;
    border: none;
    border-radius: 4px;
    font-weight: bold;
    cursor: pointer;">Hinzufügen</button>
    </form>

    @if (session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
@endif

{{--
    @if(request('unit'))
    <p>Aktuelle Gruppenauswahl: {{ request('unit') }}</p>
@else
    <p>Keine Gruppenauswahl.</p>
@endif

<p>Aktuelle URL: {{ url()->full() }}</p>
<p>Query-Parameter: {{ json_encode(request()->query()) }}</p>
--}}

</x-app-layout>
</body>
</html>