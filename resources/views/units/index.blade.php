<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initialscale=1.0">
<meta http-equiv="X-UA-Compatible" content="ie=edge">
<title>Materialdispo</title>
<link rel="stylesheet" href="{{ asset('css/style.css') }}">
@vite('resources/css/app.css')
</head>
<body>
<x-app-layout>
<x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Gruppen') }}
        </h2>
    </x-slot>
    <table>
<thead>
<tr>
<th>Bezeichnung</th>
<th>Beschreibung</th>
<th>Ändern</th>
<th>Löschen</th>
</tr>
</thead>
<tbody>
@foreach ( $units as $unit )
<tr>
{{-- <td>{{$unit->bezeichnung}}</td>  --}}
<td>
    <a href="{{ route('units.show', $unit->id) }}">
        {{ $unit->bezeichnung }}
    </a>
</td>
<td>{{$unit->description}}</td>
<td>
<a href="{{ route('units.edit', $unit->id) }}">Ändern</a>
</td>
<td>
<form action="/units/{{$unit->id}}"
method="POST">
@csrf
@method("DELETE")
<input type="submit" value="Löschen">
</form>
</td>
</tr>
@endforeach
</tbody>
</table>
<div class="bottombutton"><a href="units/create" style="background-color: orange; color: white; padding: 10px 20px; border-radius: 4px; font-weight: bold; text-decoration: none;">
            Neue  Gruppe
            </a></div>
</body>
</x-app-layout>
</html>