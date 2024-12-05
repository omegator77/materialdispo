@isset($selectedProduction)
 @if ($selectedProduction)
            <a href="{{ route('productions.index') }}" class="btn btn-secondary">Alle Produktionen anzeigen</a>
 @endif
@endisset


        <div class="w-4/5 mx-auto mt-4 bg bg-white border-gray-400 border rounded-md shadow-md overflow-hidden">
<table class="border-collapse w-full h-full bg-white">
<thead class="text-left bg-orange-400">
<tr>
<th class="text-left pl-4">Bezeichnung</th>
<th class="text-left pl-4">Beginn</th>
<th class="text-left pl-4">Ende</th>
<th class="text-left pl-4">Packen</th>
<th class="text-left pl-4">Ändern</th>
<th class="text-left pl-4">Löschen</th>



</tr>
</thead>
<tbody>
@foreach ( $productions as $production )
<tr class="even:bg-orange-200">



<td class="text-left pl-4"><a href="{{ route('productions.index', ['production_id' => $production->id]) }}">
             {{ $production->bezeichnung }}</a></td>


<td class="text-left pl-4">{{$production->booking_start ? \Carbon\Carbon::parse ($production->booking_start)->format('d.m.Y') : '/' }}</td>
<td class="text-left pl-4">{{$production->booking_end ? \Carbon\Carbon::parse ($production->booking_end)->format('d.m.Y') : '/' }}</td>
<td class="text-left pl-4"><a href="{{ route('productions.show', $production->id) }}">Packen</a></td>
<td class="text-left pl-4">
<a href="{{ route('productions.edit', $production->id) }}">Ändern</a>
</td>
<td class="text-left pl-4">
@isset($ShowDelete)
@if ($ShowDelete)
        <form action="/productions/{{$production->id}}"
                method="POST">
@csrf
@method("DELETE")
        <input type="submit" value="Löschen">
        </form>
        @endisset
        @endif
</td>


</tr>

@endforeach
</tbody>
</table>
        </div>