<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initialscale=1.0">
<meta http-equiv="X-UA-Compatible" content="ie=edge">
<title>Vorlage</title>
<link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<x-app-layout>
<body>
    <h1>Unit Details</h1>

    <p><strong>ID:</strong> {{ $unit->id }}</p>
    <p><strong>Bezeichnung:</strong> {{ $unit->bezeichnung }}</p>
    <p><strong>Beschreibung:</strong> {{ $unit->description }}</p>

    <a href="{{ route('units.index') }}">Zurück zur Übersicht</a>

    </body>
</x-app-layout>
</html>