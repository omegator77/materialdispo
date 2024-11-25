<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initialscale=1.0">
<meta http-equiv="X-UA-Compatible" content="ie=edge">
<title>Einheiten</title>
<link rel="stylesheet" href="{{ asset('css/style.css') }}">

</head>
<body>
<x-app-layout>
<x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Einheiten') }}
        </h2>
    </x-slot>
    <h1>Item Details</h1>

<p><strong>ID:</strong> {{ $item->id }}</p>
<p><strong>Bezeichnung:</strong> {{ $item->bezeichnung }}</p>
<p><strong>Nummer:</strong> {{$item->nummer }}</p>
<p><strong>Gruppe:</strong> {{ $item->unit->bezeichnung }}</p>
<p><strong>Beschreibung:</strong> {{ $item->description }}</p>
<p><strong>Angemietet:</strong> {{ $item->is_rented }}</p>
<p><strong>Mietbeginn:</strong> {{ $item->rent_start }}</p>
<p><strong>Mietende:</strong> {{ $item->rent_end }}</p>
<a href="{{ route('items.edit', $item->id) }}">Ändern</a>

<form action="/items/{{$item->id}}"
method="POST">
@csrf
@method("DELETE")
<input type="submit" value="Löschen">
</form>


<a href="{{ route('items.index') }}">Zurück zur Übersicht</a>

</body>
   
    </x-app-layout>