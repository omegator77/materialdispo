<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initialscale=1.0">
<meta http-equiv="X-UA-Compatible" content="ie=edge">
<title>Materialdispo</title>
<link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>

<body>
<x-app-layout>
<x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Gruppen') }}
        </h2>
    </x-slot>

<div class="form">

    <form action="{{ route('units.update', $unit->id) }}" method="POST">
        @csrf
        @method('PUT')

        <label for="bezeichnung">Bezeichnung:</label><br>
        <input type="text" name="bezeichnung" id="bezeichnung" value="{{ $unit->bezeichnung }}" required><br><br>

        <label for="description">Beschreibung:</label><br>
        <input type="text" name="description" id="description" value="{{ $unit->description }}"><br><br>

        <button type="submit" style=" background-color: orange;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    font-weight: bold;
    cursor: pointer;">Änderungen speichern</button>
    </form>
</div>
</body>
</x-app-layout>
</html>