@include('items.tables._filter')

<div class="max-w-7xl w-11/12 mx-auto mt-6">

    {{-- Desktop --}}
    <div class="hidden md:block bg-white border border-gray-300 rounded-lg shadow-md overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-100 border-b">
                <tr>
                    <th class="text-left px-4 py-3">
                        <a href="{{ route('items.index', array_merge(request()->all(), ['sort_by' => 'nummer', 'sort_direction' => request('sort_direction') == 'asc' ? 'desc' : 'asc'])) }}" class="hover:text-orange-500">Nr.</a>
                    </th>
                    <th class="text-left px-4 py-3">
                        <a href="{{ route('items.index', array_merge(request()->all(), ['sort_by' => 'bezeichnung', 'sort_direction' => request('sort_direction') == 'asc' ? 'desc' : 'asc'])) }}" class="hover:text-orange-500">Bezeichnung</a>
                    </th>
                    <th class="text-left px-4 py-3">Hersteller</th>
                    <th class="text-left px-4 py-3">Modell</th>
                    <th class="text-left px-4 py-3">Zoom</th>
                    <th class="text-left px-4 py-3">Seriennummer</th>
                    <th class="text-left px-4 py-3">Vermieter</th>
                    <th class="text-right px-4 py-3">Aktionen</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                <tr class="border-b hover:bg-gray-50" data-search="{{ $item->bezeichnung }} {{ $item->nummer }}">
                    <td class="px-4 py-3">{{ $item->nummer ?: '—' }}</td>
                    <td class="px-4 py-3 font-medium">
                        <a href="{{ route('items.show', $item->id) }}" class="text-gray-900 hover:text-orange-500">
                            {{ $item->bezeichnung }}
                        </a>
                    </td>
                    <td class="px-4 py-3">{{ $item->lensDetail->manufacturer ?? '—' }}</td>
                    <td class="px-4 py-3">{{ $item->lensDetail->model ?? '—' }}</td>
                    <td class="px-4 py-3">{{ $item->lensDetail->zoom_factor ?? '—' }}</td>
                    <td class="px-4 py-3">{{ $item->lensDetail->serial_number ?? '—' }}</td>
                    <td class="px-4 py-3">{{ $item->supplier->bezeichnung ?? 'Eigentum' }}</td>
                    <td class="px-4 py-3">@include('items.tables._actions')</td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-6 text-gray-500">Keine Objektive gefunden.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Mobile --}}
    <div class="md:hidden space-y-4">
        @forelse($items as $item)
        <div class="bg-white border border-gray-300 rounded-lg shadow-md p-4" data-search="{{ $item->bezeichnung }} {{ $item->nummer }}">
            <a href="{{ route('items.show', $item->id) }}" class="block text-lg font-semibold text-gray-900 hover:text-orange-500">
                {{ $item->bezeichnung }}
            </a>
            <div class="mt-3 text-sm text-gray-700 space-y-1">
                <p><strong>Hersteller:</strong> {{ $item->lensDetail->manufacturer ?? '—' }}</p>
                <p><strong>Modell:</strong> {{ $item->lensDetail->model ?? '—' }}</p>
                <p><strong>Zoom:</strong> {{ $item->lensDetail->zoom_factor ?? '—' }}</p>
                <p><strong>SN:</strong> {{ $item->lensDetail->serial_number ?? '—' }}</p>
                <p><strong>Vermieter:</strong> {{ $item->supplier->bezeichnung ?? 'Eigentum' }}</p>
            </div>
            <div class="mt-4">@include('items.tables._actions')</div>
        </div>
        @empty
        <div class="bg-white border border-gray-300 rounded-lg shadow-md p-4 text-center text-gray-500">Keine Objektive gefunden.</div>
        @endforelse
    </div>

    <div data-search-empty class="hidden mt-4 bg-white border border-gray-300 rounded-lg p-6 text-center text-gray-500">
        Keine Objektive gefunden.
    </div>

</div>
