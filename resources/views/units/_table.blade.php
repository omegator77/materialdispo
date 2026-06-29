<div class="max-w-7xl w-11/12 mx-auto mt-6">

    {{-- Desktop / Tablet --}}
    <div class="hidden md:block bg-white border border-gray-300 rounded-lg shadow-md overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-100 border-b">
                <tr>
                    <th class="text-left px-4 py-3">Gruppe</th>
                    <th class="text-left px-4 py-3">Beschreibung</th>
                    <th class="text-right px-4 py-3">Aktionen</th>
                </tr>
            </thead>

            <tbody>
                @forelse($units as $unit)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium">
                            <a href="{{ route('items.index', ['unit_id' => $unit->id]) }}"
                               class="text-gray-900 hover:text-orange-500">
                                {{ $unit->bezeichnung }}
                            </a>
                        </td>

                        <td class="px-4 py-3">
                            {{ $unit->description ?: '—' }}
                        </td>

                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('units.show', $unit->id) }}"
                                   class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-1 px-3 rounded">
                                    Details
                                </a>

                                @if(Auth::user()->isUser())
                                <a href="{{ route('units.edit', $unit->id) }}"
                                   class="bg-orange-400 hover:bg-orange-500 text-white font-semibold py-1 px-3 rounded">
                                    Bearbeiten
                                </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center py-6 text-gray-500">
                            Noch keine Gruppen angelegt.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Handy --}}
    <div class="md:hidden space-y-3">
        @forelse($units as $unit)
            <div class="bg-white border border-gray-300 rounded-lg shadow-md p-4">
                <div>
                    <a href="{{ route('items.index', ['unit_id' => $unit->id]) }}"
                       class="text-lg font-semibold text-gray-900 hover:text-orange-500">
                        {{ $unit->bezeichnung }}
                    </a>

                    <p class="text-sm text-gray-500">
                        {{ $unit->description ?: 'Keine Beschreibung hinterlegt' }}
                    </p>
                </div>

                <div class="mt-4 flex gap-2">
                    <a href="{{ route('units.show', $unit->id) }}"
                       class="flex-1 text-center bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-3 rounded">
                        Details
                    </a>

                    @if(Auth::user()->isUser())
                    <a href="{{ route('units.edit', $unit->id) }}"
                       class="flex-1 text-center bg-orange-400 hover:bg-orange-500 text-white font-semibold py-2 px-3 rounded">
                        Bearbeiten
                    </a>
                    @endif
                </div>
            </div>
        @empty
            <div class="bg-white border border-gray-300 rounded-lg p-6 text-center text-gray-500">
                Noch keine Gruppen angelegt.
            </div>
        @endforelse
    </div>

</div>