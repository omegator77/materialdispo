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
    @if ($selectedProduction)
            <a href="{{ route('productions.index') }}" class="btn btn-secondary">Alle Produktionen anzeigen</a>
        @endif
<table>
<thead>
<tr>
<th>Bezeichnung</th>
<th>Beginn</th>
<th>Ende</th>
<th>Packen</th>
<th>Ändern</th>
<th>Löschen</th>



</tr>
</thead>
<tbody>
@foreach ( $productions as $production )
<tr>



<td><a href="{{ route('productions.index', ['production_id' => $production->id]) }}">
             {{ $production->bezeichnung }}</a></td>


<td>{{$production->booking_start ? \Carbon\Carbon::parse ($production->booking_start)->format('d.m.Y') : '/' }}</td>
<td>{{$production->booking_end ? \Carbon\Carbon::parse ($production->booking_end)->format('d.m.Y') : '/' }}</td>
<td><a href="{{ route('productions.show', $production->id) }}">Packen</a></td>
<td>
<a href="{{ route('productions.edit', $production->id) }}">Ändern</a>
</td>
<td>
<form action="/productions/{{$production->id}}"
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
<div class="bottombutton"><a href="productions/create" style="background-color: orange; color: white; padding: 10px 20px; border-radius: 4px; font-weight: bold; text-decoration: none;">
            Neue Produktion
            </a></div>

</x-app-layout>
</body>
</html>