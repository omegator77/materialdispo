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

    <div class="hidden md:block bg-white border border-gray-300 rounded-lg shadow-md overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-100 border-b">
                <tr>
                    <th class="text-left px-4 py-3">Nr.</th>
                    <th class="text-left px-4 py-3">Bezeichnung</th>
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
                    <tr class="border-b hover:bg-gray-50">

                    <td class="px-4 py-3">
                            {{ $item->nummer ?: '—' }}
                        </td>

                        <td class="px-4 py-3 font-medium">
                            <a href="{{ route('items.show', $item->id) }}"
                               class="text-gray-900 hover:text-orange-500">
                                {{ $item->bezeichnung }}
                            </a>
                        </td>

                        <td class="px-4 py-3">
                            {{ $item->lensDetail->manufacturer ?? '—' }}
                        </td>

                        <td class="px-4 py-3">
                            {{ $item->lensDetail->model ?? '—' }}
                        </td>

                        <td class="px-4 py-3">
                            {{ $item->lensDetail->zoom_factor ?? '—' }}
                        </td>

                        <td class="px-4 py-3">
                            {{ $item->lensDetail->serial_number ?? '—' }}
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
                        <td colspan="7" class="text-center py-6 text-gray-500">
                            Keine Objektive gefunden.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>