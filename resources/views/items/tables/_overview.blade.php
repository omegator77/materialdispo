{{-- Filter --}}
<div class="max-w-7xl w-11/12 mx-auto mt-6">
    <div class="bg-white border border-gray-300 rounded-lg shadow-md p-4">
        <form method="GET" action="{{ route('items.index') }}">
            <div class="flex flex-col sm:flex-row sm:items-end gap-4">
                <div class="w-full sm:w-72">
                    <label for="unitFilter" class="block text-sm font-medium text-gray-700 mb-1">
                        Gruppe filtern
                    </label>

                    <select
                        id="unitFilter"
                        name="unit_id"
                        class="form-control w-full"
                        onchange="this.form.submit()">
                        <option value="">Alle Gruppen</option>

                        @foreach($units as $unit)
                        <option value="{{ $unit->id }}"
                            {{ (request('unit_id') ?? '') == $unit->id ? 'selected' : '' }}>
                            {{ $unit->bezeichnung }}
                        </option>
                        @endforeach
                    </select>
                </div>

                @if(request('unit_id'))
                <div>
                    <a href="{{ route('items.index') }}"
                        class="inline-flex justify-center bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-4 rounded">
                        Filter zurücksetzen
                    </a>
                </div>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="max-w-7xl w-11/12 mx-auto mt-6">

    {{-- Desktop / Tablet --}}
    <div class="hidden md:block bg-white border border-gray-300 rounded-lg shadow-md overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-100 border-b">
                <tr>
                    <th class="text-left px-4 py-3">
                        <a href="{{ route('items.index', array_merge(request()->all(), ['sort_by' => 'units_id', 'sort_direction' => request('sort_direction') == 'asc' ? 'desc' : 'asc'])) }}"
                            class="hover:text-orange-500">
                            Gruppe
                        </a>
                    </th>

                    <th class="text-left px-4 py-3">
                        <a href="{{ route('items.index', array_merge(request()->all(), ['sort_by' => 'nummer', 'sort_direction' => request('sort_direction') == 'asc' ? 'desc' : 'asc'])) }}"
                            class="hover:text-orange-500">
                            Nr.
                        </a>
                    </th>

                    <th class="text-left px-4 py-3">
                        <a href="{{ route('items.index', array_merge(request()->all(), ['sort_by' => 'bezeichnung', 'sort_direction' => request('sort_direction') == 'asc' ? 'desc' : 'asc'])) }}"
                            class="hover:text-orange-500">
                            Bezeichnung
                        </a>
                    </th>

                    <th class="text-left px-4 py-3">Beschreibung</th>

                    <th class="text-left px-4 py-3">Vermieter</th>

                    <th class="text-right px-4 py-3">Aktionen</th>
                </tr>
            </thead>

            <tbody>
                @forelse($items as $item)
                <tr class="border-b hover:bg-gray-50">
                    <td class="px-4 py-3">
                        {{ $item->unit->bezeichnung ?? '—' }}
                    </td>

                    <td class="px-4 py-3">
                        {{ $item->nummer ?: '—' }}
                    </td>

                    <td class="px-4 py-3 font-medium">
                        <a href="{{ route('items.show', $item->id) }}"
                            class="text-gray-900 hover:text-orange-500">
                            {{ $item->bezeichnung }}
                        </a>
                    </td>

                    <td class="px-4 py-3 text-gray-700">
                        {{ $item->description ?: '—' }}
                    </td>

                    <td class="px-4 py-3">
                        {{ $item->supplier->bezeichnung ?? 'Eigentum' }}
                    </td>

                    <td class="px-4 py-3">
                        <div class="flex justify-end gap-2">
                            <a href="{{ route('items.show', $item->id) }}"
                                class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-1 px-3 rounded">
                                Details
                            </a>

                            <a href="{{ route('items.edit', $item->id) }}"
                                class="bg-orange-400 hover:bg-orange-500 text-white font-semibold py-1 px-3 rounded">
                                Bearbeiten
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-6 text-gray-500">
                        Keine Einheiten gefunden.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Handy --}}
    <div class="md:hidden space-y-3">
        @forelse($items as $item)
        <div class="bg-white border border-gray-300 rounded-lg shadow-md p-4">
            <div>
                <a href="{{ route('items.show', $item->id) }}"
                    class="text-lg font-semibold text-gray-900 hover:text-orange-500">
                    {{ $item->bezeichnung }}
                </a>

                <p class="text-sm text-gray-500">
                    {{ $item->unit->bezeichnung ?? 'Keine Gruppe' }}
                </p>
            </div>

            <div class="mt-3 space-y-1 text-sm text-gray-700">
                <p>
                    <span class="font-medium">Nummer:</span>
                    {{ $item->nummer ?: '—' }}
                </p>

                <p>
                    <span class="font-medium">Beschreibung:</span>
                    {{ $item->description ?: '—' }}
                </p>

                <p>
                    <span class="font-medium">Vermieter:</span>
                    {{ $item->supplier->bezeichnung ?? 'Eigentum' }}
                </p>

                @if($item->suppliers_id)
                <p>
                    <span class="font-medium">Mietzeitraum:</span>
                    {{ $item->rent_start ? \Carbon\Carbon::parse($item->rent_start)->format('d.m.Y') : '—' }}
                    –
                    {{ $item->rent_end ? \Carbon\Carbon::parse($item->rent_end)->format('d.m.Y') : '—' }}
                </p>
                @endif
            </div>

            <div class="mt-4 flex gap-2">
                <a href="{{ route('items.show', $item->id) }}"
                    class="flex-1 text-center bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-3 rounded">
                    Details
                </a>

                <a href="{{ route('items.edit', $item->id) }}"
                    class="flex-1 text-center bg-orange-400 hover:bg-orange-500 text-white font-semibold py-2 px-3 rounded">
                    Bearbeiten
                </a>
            </div>
        </div>
        @empty
        <div class="bg-white border border-gray-300 rounded-lg p-6 text-center text-gray-500">
            Keine Einheiten gefunden.
        </div>
        @endforelse
    </div>

</div>