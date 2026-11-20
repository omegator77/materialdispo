<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Gerätetypen">
            <x-slot name="actions">
                @if(Auth::user()->isUser())
            <a href="{{ route('geraetetypen.create') }}"
               class="btn btn-primary">
                Neuer Gerätetyp
            </a>
            @endif
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="page" x-data="geraetetypSearch()">

        @if(session('success'))
        <div class="bg-green-50 border border-green-300 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
        @endif

        @if(session('error'))
        <div class="bg-red-50 border border-red-300 text-red-700 px-4 py-3 rounded mb-4">
            {{ session('error') }}
        </div>
        @endif

        {{-- Suche & Filter --}}
        <div class="card p-4 mb-4">
            <div class="flex flex-col sm:flex-row sm:items-end gap-4">
                <div class="w-full sm:w-72">
                    <label for="geraetetypUnitFilter" class="form-label">
                        Gruppe filtern
                    </label>
                    <select id="geraetetypUnitFilter" x-model="unitId" @change="filter()" class="form-control w-full">
                        <option value="">Alle Gruppen</option>
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->bezeichnung }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="w-full sm:flex-1">
                    <label for="geraetetypSearch" class="form-label">
                        Suche
                    </label>
                    <input
                        type="text"
                        id="geraetetypSearch"
                        x-model="query"
                        @input.debounce.150ms="filter()"
                        placeholder="Bezeichnung oder Bemerkung durchsuchen…"
                        class="form-control w-full"
                        autocomplete="off"
                    >
                </div>
            </div>
        </div>

        {{-- Desktop / Tablet --}}
        <div class="hidden md:block card overflow-hidden">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Bezeichnung</th>
                        <th>Gruppe</th>
                        <th>Bemerkung</th>
                        <th>Geräte</th>
                        <th class="text-right">Aktionen</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($geraetetypen as $geraetetyp)
                        <tr class="border-b hover:bg-gray-50"
                            data-search="{{ $geraetetyp->bezeichnung }} {{ $geraetetyp->description }}"
                            data-unit-id="{{ $geraetetyp->units_id }}">
                            <td class="px-4 py-3 font-medium text-gray-900">
                                {{ $geraetetyp->bezeichnung }}
                            </td>
                            <td>
                                {{ $geraetetyp->unit->bezeichnung ?? '—' }}
                            </td>
                            <td>
                                {{ $geraetetyp->description ?: '—' }}
                            </td>
                            <td>
                                {{ $geraetetyp->items_count }}
                            </td>
                            <td>
                                @if(Auth::user()->isUser())
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('geraetetypen.edit', $geraetetyp->id) }}"
                                       class="btn btn-primary btn-sm">
                                        Bearbeiten
                                    </a>

                                    <form action="{{ route('geraetetypen.destroy', $geraetetyp->id) }}"
                                          method="POST"
                                          onsubmit="return confirm('Diesen Gerätetyp wirklich löschen?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="btn btn-danger btn-sm">
                                            Löschen
                                        </button>
                                    </form>
                                </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="empty-state">
                                Noch keine Gerätetypen angelegt.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div data-search-empty class="hidden text-center py-6 text-gray-500">
                Keine Gerätetypen gefunden.
            </div>
        </div>

        {{-- Handy --}}
        <div class="md:hidden space-y-3">
            @forelse($geraetetypen as $geraetetyp)
                <div class="card p-4"
                     data-search="{{ $geraetetyp->bezeichnung }} {{ $geraetetyp->description }}"
                     data-unit-id="{{ $geraetetyp->units_id }}">
                    <div class="font-semibold text-gray-900">
                        {{ $geraetetyp->bezeichnung }}
                    </div>
                    <div class="text-sm text-gray-500">
                        {{ $geraetetyp->unit->bezeichnung ?? '—' }}
                    </div>
                    @if($geraetetyp->description)
                    <div class="text-sm text-gray-600 mt-1">
                        {{ $geraetetyp->description }}
                    </div>
                    @endif

                    @if(Auth::user()->isUser())
                    <div class="flex gap-2 mt-3">
                        <a href="{{ route('geraetetypen.edit', $geraetetyp->id) }}"
                           class="btn btn-primary btn-sm flex-1 text-center">
                            Bearbeiten
                        </a>
                        <form action="{{ route('geraetetypen.destroy', $geraetetyp->id) }}"
                              method="POST"
                              onsubmit="return confirm('Diesen Gerätetyp wirklich löschen?');"
                              class="flex-1">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="btn btn-danger btn-sm w-full">
                                Löschen
                            </button>
                        </form>
                    </div>
                    @endif
                </div>
            @empty
                <div class="card card-pad text-center text-gray-500">
                    Noch keine Gerätetypen angelegt.
                </div>
            @endforelse

            <div data-search-empty class="hidden card card-pad text-center text-gray-500">
                Keine Gerätetypen gefunden.
            </div>
        </div>
    </div>

    <script>
    function geraetetypSearch() {
        return {
            query: '',
            unitId: '',
            filter() {
                const needle = this.query.trim().toLowerCase();
                const rows = document.querySelectorAll('[data-search]');
                let visibleCount = 0;

                rows.forEach(el => {
                    const matchesSearch = needle === '' || el.dataset.search.toLowerCase().includes(needle);
                    const matchesUnit = this.unitId === '' || el.dataset.unitId === this.unitId;
                    const matches = matchesSearch && matchesUnit;
                    el.classList.toggle('hidden', !matches);
                    if (matches) visibleCount++;
                });

                document.querySelectorAll('[data-search-empty]').forEach(el => {
                    el.classList.toggle('hidden', !((needle !== '' || this.unitId !== '') && visibleCount === 0 && rows.length > 0));
                });
            }
        };
    }
    </script>
</x-app-layout>
