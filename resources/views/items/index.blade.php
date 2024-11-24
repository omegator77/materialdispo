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
   <table>
<thead>
<tr>
<th>Bezeichnung</th>
<th>Beschreibung</th>
{{-- <th>Menge</th> --}}
<th>Gruppe</th>
<th>Angemietet</th>
<th>Vermieter</th>
<th>Miete von</th>
<th>Miete bis</th>
<th>Ändern</th>
<th>Löschen</th>
</tr>
</thead>
<tbody>
@foreach ( $items as $item )
<tr>
<td>{{$item->bezeichnung}}</td>
<td>{{$item->description}}</td>
{{-- <td>{{$item->quantity}}</td> --}}
<td>{{$item->unit->bezeichnung}}</td>
<td>{{$item->is_rented == 1 ? 'Ja' : 'Nein' }}</td>
<td>{{$item->supplier->bezeichnung ?? 'Eigentum'}}</td>
<td>{{$item->rent_start ? \Carbon\Carbon::parse ($item->rent_start)->format('d.m.Y') : '/' }}</td>
<td>{{$item->rent_end ? \Carbon\Carbon::parse ($item->rent_end)->format('d.m.Y') : '/' }}</td>
<td>
<a href="{{ route('items.edit', $item->id) }}">Ändern</a>
</td>
<td>
<form action="/items/{{$item->id}}"
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
<div class="bottombutton"><a href="items/create" style="background-color: orange; color: white; padding: 10px 20px; border-radius: 4px; font-weight: bold; text-decoration: none;">
            Neue Einheit
            </a></div>
</x-app-layout>
</body>
</html>