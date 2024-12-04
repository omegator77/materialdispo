<div class="w-4/5 mx-auto mt-4 bg bg-white border-gray-400 border rounded-md shadow-md overflow-hidden">
<table class="border-collapse w-full h-full bg-white">
<thead class="text-left bg-orange-400">
<tr>
<th class="text left pl-4 ">Bezeichnung</th>
<th class="text left pl-4">Beschreibung</th>
<th class="text left pl-4">Ändern</th>
<th class="text left pl-4">Löschen</th>
</tr>
</thead>
<tbody>
@foreach ( $units as $unit )
<tr class="even:bg-orange-200">
<td class="text left pl-4">
    <a href="{{ route('units.show', $unit->id) }}">
        {{ $unit->bezeichnung }}
    </a>
</td>
<td class="text left pl-4">{{$unit->description}}</td>
<td class="text left pl-4">
<a href="{{ route('units.edit', $unit->id) }}" class="hover:text-gray-700 hover:font-extrabold">Ändern</a>
</td>
<td  class="text left pl-4">
<form action="/units/{{$unit->id}}"
method="POST">
@csrf
@method("DELETE")
<input type="submit" value="Löschen" class="hover:text-gray-700 hover:font-extrabold cursor-pointer">
</form>
</td>
</tr>
@endforeach
</tbody>
</table>
</div>