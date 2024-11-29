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
            {{ __('Produktionen') }}
        </h2>
    </x-slot>

<div class="form">
<form action="{{ route('productions.update', $production->id) }}" method="POST">
        @csrf
        @method('PUT')

<label for="bezeichnung">Bezeichnung: </label>
<input
type="text"
name="bezeichnung"
id="bezeichnung"
value="{{ $production->bezeichnung }}"><br>


<br>
<!-- Eingabefeld für Mietbeginn (booking_start) -->
    <label for="booking_start">Mietbeginn:</label>
    <input type="text" name="booking_start" id="booking_start" class="form-control" value="{{ \Carbon\Carbon::parse($production->booking_start)->format('d.m.Y') }}"><br>

    <!-- Eingabefeld für Mietende (booking_end) -->
    <label for="booking_end">Mietende:</label>
    <input type="text" name="booking_end" id="booking_end" class="form-control" value="{{ \Carbon\Carbon::parse($production->booking_end)->format('d.m.Y') }}"><br>

<button type="submit" style=" background-color: orange;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    font-weight: bold;
    cursor: pointer;">
    Produktion speichern
</button>

{{-- <div class="errors"> --}}
@if ($errors->any())
<ul>
@foreach ($errors->all() as $error)
<li>{{ $error }}</li>
@endforeach
</ul>
@endif

</div>

</body>
</x-app-layout>
</html>