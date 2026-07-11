<div class="max-w-7xl w-11/12 mx-auto mt-6" x-data="unitSearch()">

    {{-- Suche --}}
    <div class="bg-white border border-gray-300 rounded-lg shadow-md p-4 mb-4">
        <label for="unitSearch" class="block text-sm font-medium text-gray-700 mb-1">
            Suche
        </label>
        <input
            type="text"
            id="unitSearch"
            x-model="query"
            @input.debounce.150ms="filter()"
            placeholder="Gruppe oder Beschreibung durchsuchen…"
            class="form-control w-full"
            autocomplete="off"
        >
    </div>

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
                    <tr class="border-b hover:bg-gray-50" data-search="{{ $unit->bezeichnung }} {{ $unit->description }}">
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
                                @if(Auth::user()->isUser())
                                <form method="POST" action="{{ route('units.reorder', $unit) }}">
                                    @csrf
                                    <input type="hidden" name="direction" value="up">
                                    <button type="submit"
                                            title="Nach oben verschieben"
                                            @disabled($loop->first)
                                            class="bg-gray-100 hover:bg-gray-200 disabled:opacity-30 disabled:cursor-not-allowed text-gray-700 font-semibold py-1 px-2 rounded">
                                        &#9650;
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('units.reorder', $unit) }}">
                                    @csrf
                                    <input type="hidden" name="direction" value="down">
                                    <button type="submit"
                                            title="Nach unten verschieben"
                                            @disabled($loop->last)
                                            class="bg-gray-100 hover:bg-gray-200 disabled:opacity-30 disabled:cursor-not-allowed text-gray-700 font-semibold py-1 px-2 rounded">
                                        &#9660;
                                    </button>
                                </form>
                                @endif

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

        <div data-search-empty class="hidden text-center py-6 text-gray-500">
            Keine Gruppe gefunden.
        </div>
    </div>

    {{-- Handy --}}
    <div class="md:hidden space-y-3">
        @forelse($units as $unit)
            <div class="bg-white border border-gray-300 rounded-lg shadow-md p-4" data-search="{{ $unit->bezeichnung }} {{ $unit->description }}">
                <div>
                    <a href="{{ route('items.index', ['unit_id' => $unit->id]) }}"
                       class="text-lg font-semibold text-gray-900 hover:text-orange-500">
                        {{ $unit->bezeichnung }}
                    </a>

                    <p class="text-sm text-gray-500">
                        {{ $unit->description ?: 'Keine Beschreibung hinterlegt' }}
                    </p>
                </div>

                @if(Auth::user()->isUser())
                <div class="mt-3 flex gap-2">
                    <form method="POST" action="{{ route('units.reorder', $unit) }}" class="flex-1">
                        @csrf
                        <input type="hidden" name="direction" value="up">
                        <button type="submit"
                                title="Nach oben verschieben"
                                @disabled($loop->first)
                                class="w-full text-center bg-gray-100 hover:bg-gray-200 disabled:opacity-30 disabled:cursor-not-allowed text-gray-700 font-semibold py-2 px-3 rounded">
                            &#9650; Nach oben
                        </button>
                    </form>

                    <form method="POST" action="{{ route('units.reorder', $unit) }}" class="flex-1">
                        @csrf
                        <input type="hidden" name="direction" value="down">
                        <button type="submit"
                                title="Nach unten verschieben"
                                @disabled($loop->last)
                                class="w-full text-center bg-gray-100 hover:bg-gray-200 disabled:opacity-30 disabled:cursor-not-allowed text-gray-700 font-semibold py-2 px-3 rounded">
                            &#9660; Nach unten
                        </button>
                    </form>
                </div>
                @endif

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

        <div data-search-empty class="hidden bg-white border border-gray-300 rounded-lg p-6 text-center text-gray-500">
            Keine Gruppe gefunden.
        </div>
    </div>

</div>

<script>
function unitSearch() {
    return {
        query: '',
        filter() {
            const needle = this.query.trim().toLowerCase();
            const rows = document.querySelectorAll('[data-search]');
            let visibleCount = 0;

            rows.forEach(el => {
                const haystack = el.dataset.search.toLowerCase();
                const matches = needle === '' || haystack.includes(needle);
                el.classList.toggle('hidden', !matches);
                if (matches) visibleCount++;
            });

            document.querySelectorAll('[data-search-empty]').forEach(el => {
                el.classList.toggle('hidden', !(needle !== '' && visibleCount === 0 && rows.length > 0));
            });
        }
    };
}
</script>
