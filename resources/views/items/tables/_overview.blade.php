@include('items.tables._filter')

<div class="max-w-7xl w-11/12 mx-auto mt-6">

    {{-- Desktop --}}
    <div class="hidden md:block bg-white border border-gray-300 rounded-lg shadow-md overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-100 border-b">
                <tr>
                    <th class="text-left px-4 py-3">
                        <a href="{{ route('items.index', array_merge(request()->all(), ['sort_by' => 'units_id', 'sort_direction' => request('sort_direction') == 'asc' ? 'desc' : 'asc'])) }}" class="hover:text-orange-500">Gruppe</a>
                    </th>
                    <th class="text-left px-4 py-3">
                        <a href="{{ route('items.index', array_merge(request()->all(), ['sort_by' => 'nummer', 'sort_direction' => request('sort_direction') == 'asc' ? 'desc' : 'asc'])) }}" class="hover:text-orange-500">Nr.</a>
                    </th>
                    <th class="text-left px-4 py-3">
                        <a href="{{ route('items.index', array_merge(request()->all(), ['sort_by' => 'bezeichnung', 'sort_direction' => request('sort_direction') == 'asc' ? 'desc' : 'asc'])) }}" class="hover:text-orange-500">Bezeichnung</a>
                    </th>
                    <th class="text-left px-4 py-3">Beschreibung</th>
                    <th class="text-left px-4 py-3">Vermieter</th>
                    <th class="text-right px-4 py-3">Aktionen</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                <tr class="border-b hover:bg-gray-50">
                    <td class="px-4 py-3">{{ $item->unit->bezeichnung ?? '—' }}</td>
                    <td class="px-4 py-3">{{ $item->nummer ?: '—' }}</td>
                    <td class="px-4 py-3 font-medium">
                        <a href="{{ route('items.show', $item->id) }}" class="text-gray-900 hover:text-orange-500">
                            {{ $item->bezeichnung }}
                        </a>
                    </td>
                    <td class="px-4 py-3 text-gray-700">{{ $item->description ?: '—' }}</td>
                    <td class="px-4 py-3">{{ $item->supplier->bezeichnung ?? 'Eigentum' }}</td>
                    <td class="px-4 py-3">@include('items.tables._actions')</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-6 text-gray-500">Keine Geräte gefunden.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Mobile --}}
    <div class="md:hidden space-y-3">
        @forelse($items as $item)
        <div class="bg-white border border-gray-300 rounded-lg shadow-md p-4">
            <a href="{{ route('items.show', $item->id) }}" class="text-lg font-semibold text-gray-900 hover:text-orange-500">
                {{ $item->bezeichnung }}
            </a>
            <p class="text-sm text-gray-500">{{ $item->unit->bezeichnung ?? 'Keine Gruppe' }}</p>
            <div class="mt-3 space-y-1 text-sm text-gray-700">
                <p><span class="font-medium">Nummer:</span> {{ $item->nummer ?: '—' }}</p>
                <p><span class="font-medium">Beschreibung:</span> {{ $item->description ?: '—' }}</p>
                <p><span class="font-medium">Vermieter:</span> {{ $item->supplier->bezeichnung ?? 'Eigentum' }}</p>
                @if($item->suppliers_id)
                <p><span class="font-medium">Mietzeitraum:</span>
                    {{ $item->rent_start ? \Carbon\Carbon::parse($item->rent_start)->format('d.m.Y') : '—' }}
                    – {{ $item->rent_end ? \Carbon\Carbon::parse($item->rent_end)->format('d.m.Y') : '—' }}
                </p>
                @endif
            </div>
            <div class="mt-4">@include('items.tables._actions')</div>
        </div>
        @empty
        <div class="bg-white border border-gray-300 rounded-lg p-6 text-center text-gray-500">Keine Geräte gefunden.</div>
        @endforelse
    </div>

</div>
