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
            {{ __('Einheiten') }}
        </h2>
    </x-slot>
    <div class="form">
    <form action="/items" method="POST">
@csrf

<label for="units_id">Gruppe auswählen:</label>
    <select name="units_id" id="units_id" class="form-select">
        @foreach($units as $unit)
            <option value="{{ $unit->id }}">{{ $unit->bezeichnung }}</option>
        @endforeach
    </select>
    <br>

<label for="bezeichnung">Bezeichnung: </label>
<input
type="text"
name="bezeichnung"
id="bezeichnung"
placeholder="Bezeichnung ..."
value="{{ old('bezeichnung') }}"><br>

<label for="description">Bemerkung: </label>
<input
type="text"
name="description"
id="description"
placeholder="Bemerkung ..."
value="{{ old('description') }}"><br>
<br>
<div class="form-check">
        <input type="checkbox" name="is_rented" id="is_rented" value="1" class="form-check-input">
        <label for="is_rented" class="form-check-label">Ist gemietet</label>
    </div>
<br>
<label for="suppliers_id">Vermieter:</label>
    <select name="suppliers_id" id="suppliers_id" class="form-select">
    <option value="" selected>-- Bitte wählen --</option>
        @foreach($suppliers as $supplier)
            <option value="{{ $supplier->id }}">{{ $supplier->bezeichnung }}</option>
        @endforeach
    </select>
    <br>
<!-- Eingabefeld für Mietbeginn (rent_start) -->
<label for="rent_start">Mietbeginn:</label>
    <input type="text" name="rent_start" id="rent_start" class="form-control" placeholder="TT.MM.JJJJ"><br>

    <!-- Eingabefeld für Mietende (rent_end) -->
    <label for="rent_end">Mietende:</label>
    <input type="text" name="rent_end" id="rent_end" class="form-control" placeholder="TT.MM.JJJJ">

<button type="submit" style=" background-color: orange;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    font-weight: bold;
    cursor: pointer;">
    Einheit speichern
</button>

{{-- <div class="errors"> --}}
@if ($errors->any())
<ul>
@foreach ($errors->all() as $error)
<li>{{ $error }}</li>
@endforeach
</ul>
@endif
{{-- </div> --}}

</div>
</body>
</x-app-layout>
</html>