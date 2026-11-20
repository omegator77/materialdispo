<div class="page" x-data="unitSearch()">

    {{-- Suche --}}
    <div class="card p-4 mb-4">
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
    <div class="hidden md:block card overflow-hidden">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Gruppe</th>
                    <th>Beschreibung</th>
                    <th class="text-right">Aktionen</th>
                </tr>
            </thead>

            <tbody>
                @forelse($units as $unit)
                    <tr class="border-b hover:bg-gray-50" data-search="{{ $unit->bezeichnung }} {{ $unit->description }}">
                        <td class="font-medium">
                            <a href="{{ route('items.index', ['unit_id' => $unit->id]) }}"
                               class="text-gray-900 hover:text-orange-500">
                                {{ $unit->bezeichnung }}
                            </a>
                        </td>

                        <td>
                            {{ $unit->description ?: '—' }}
                        </td>

                        <td>
                            <div class="flex justify-end gap-2">
                                @if(Auth::user()->isUser())
                                <form method="POST" action="{{ route('units.reorder', $unit) }}">
                                    @csrf
                                    <input type="hidden" name="direction" value="up">
                                    <button type="submit"
                                            title="Nach oben verschieben"
                                            @disabled($loop->first)
                                            class="btn btn-secondary btn-sm disabled:opacity-30 disabled:cursor-not-allowed">
                                        &#9650;
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('units.reorder', $unit) }}">
                                    @csrf
                                    <input type="hidden" name="direction" value="down">
                                    <button type="submit"
                                            title="Nach unten verschieben"
                                            @disabled($loop->last)
                                            class="btn btn-secondary btn-sm disabled:opacity-30 disabled:cursor-not-allowed">
                                        &#9660;
                                    </button>
                                </form>
                                @endif

                                <a href="{{ route('units.show', $unit->id) }}"
                                   class="btn btn-secondary btn-sm">
                                    Details
                                </a>

                                @if(Auth::user()->isUser())
                                <a href="{{ route('units.edit', $unit->id) }}"
                                   class="btn btn-primary btn-sm">
                                    Bearbeiten
                                </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="empty-state">
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
            <div class="card p-4" data-search="{{ $unit->bezeichnung }} {{ $unit->description }}">
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
                                class="btn btn-secondary w-full text-center disabled:opacity-30 disabled:cursor-not-allowed">
                            &#9650; Nach oben
                        </button>
                    </form>

                    <form method="POST" action="{{ route('units.reorder', $unit) }}" class="flex-1">
                        @csrf
                        <input type="hidden" name="direction" value="down">
                        <button type="submit"
                                title="Nach unten verschieben"
                                @disabled($loop->last)
                                class="btn btn-secondary w-full text-center disabled:opacity-30 disabled:cursor-not-allowed">
                            &#9660; Nach unten
                        </button>
                    </form>
                </div>
                @endif

                <div class="mt-4 flex gap-2">
                    <a href="{{ route('units.show', $unit->id) }}"
                       class="btn btn-secondary flex-1 text-center">
                        Details
                    </a>

                    @if(Auth::user()->isUser())
                    <a href="{{ route('units.edit', $unit->id) }}"
                       class="btn btn-primary flex-1 text-center">
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
