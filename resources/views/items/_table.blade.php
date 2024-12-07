<!-- Filterformular -->
<div class=" max-w-7xl w-4/5  mx-auto mt-6 bg-white p-6 border border-gray-400 rounded-md shadow-md">
        <form method="GET" action="{{ route('items.index') }}">
            <div class="text-center">
                <!-- Filter nach Gruppe (Unit) -->
                <label for="unitFilter">Gruppe:</label>
                <select class="rounded-md" id="unitFilter" name="unit_id" onchange="this.form.submit()">
                    <option value="">Alle Gruppen</option>
                    @foreach($units as $unit)
                        <option value="{{ $unit->id }}" 
                            {{ (request('unit_id') ?? '') == $unit->id ? 'selected' : '' }}>
                            {{ $unit->bezeichnung }}
                        </option>
                    @endforeach
                </select>                  
        </form>
        </div>

    </div>
    <div class="overflow-x-auto  w-4/5 mx-auto mt-4 bg bg-white border-gray-400 border rounded-md shadow-md overflow-hidden">
<table class="border-collapse w-full h-full bg-white">
<thead class="text-left bg-orange-400">
<tr>
<th><a href="{{ route('items.index', array_merge(request()->all(), ['sort_by' => 'bezeichnung', 'sort_direction' => request('sort_direction') == 'asc' ? 'desc' : 'asc'])) }}" class="text-left pl-4">Bezeichnung</a></th>
{{-- <th>Beschreibung</th>  --}}
<th><a href="{{ route('items.index', array_merge(request()->all(), ['sort_by' => 'nummer', 'sort_direction' => request('sort_direction') == 'asc' ? 'desc' : 'asc'])) }}" class="text-left pl-4">Nummer</a></th>
{{-- <th>Menge</th> --}}
<th><a href="{{ route('items.index', array_merge(request()->all(), ['sort_by' => 'units_id', 'sort_direction' => request('sort_direction') == 'asc' ? 'desc' : 'asc'])) }}" class="text-left pl-4">Gruppe</a></th>
{{-- <th>Gruppe</th> --}}
<th class="text-left pl-4">Angemietet</th>
<th class="text-left pl-4">Vermieter</th>
<th><a href="{{ route('items.index', array_merge(request()->all(), ['sort_by' => 'rent_start', 'sort_direction' => request('sort_direction') == 'asc' ? 'desc' : 'asc'])) }}" class="text-left pl-4">Miete von</a></th>
<th><a href="{{ route('items.index', array_merge(request()->all(), ['sort_by' => 'rent_end', 'sort_direction' => request('sort_direction') == 'asc' ? 'desc' : 'asc'])) }}" class="text-left pl-4">Miete bis</a></th>
</tr>
</thead>
<tbody>

@foreach ( $items as $item )
<tr class="even:bg-orange-200">
<td class="text-left pl-4 hover:font-bold"><a href="{{ route('items.show', $item->id) }}">
        {{ $item->bezeichnung }}
    </a></td>
{{-- <td>{{$item->description}}</td>  --}}
<td class="text-left pl-4">{{$item->nummer}}</td>
{{-- <td>{{$item->quantity}}</td> --}}
<td class="text-left pl-4">{{$item->unit->bezeichnung}}</td>
<td class="text-left pl-4">{{$item->is_rented == 1 ? 'Ja' : 'Nein' }}</td>
<td class="text-left pl-4">{{$item->supplier->bezeichnung ?? 'Eigentum'}}</td>
<td class="text-left pl-4">{{$item->rent_start ? \Carbon\Carbon::parse ($item->rent_start)->format('d.m.Y') : '/' }}</td>
<td class="text-left pl-4">{{$item->rent_end ? \Carbon\Carbon::parse ($item->rent_end)->format('d.m.Y') : '/' }}</td>

</tr>
@endforeach
</tbody>
</table>
    </div>