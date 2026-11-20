<div class="page" x-data="itemSearch()">
    <div class="card p-4">
        <div class="flex flex-col sm:flex-row sm:items-end gap-4">
            <form method="GET" action="{{ route('items.index') }}" class="contents">
                <div class="w-full sm:w-72">
                    <label for="unitFilter" class="form-label">
                        Gruppe filtern
                    </label>
                    <select id="unitFilter" name="unit_id" class="form-control w-full" onchange="this.form.submit()">
                        <option value="">Alle Gruppen</option>
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}" {{ (request('unit_id') ?? '') == $unit->id ? 'selected' : '' }}>
                                {{ $unit->bezeichnung }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>

            <div class="w-full sm:flex-1">
                <label for="itemSearch" class="form-label">
                    Suche
                </label>
                <input
                    type="text"
                    id="itemSearch"
                    x-model="query"
                    @input.debounce.150ms="filter()"
                    placeholder="Bezeichnung oder Nummer durchsuchen…"
                    class="form-control w-full"
                    autocomplete="off"
                >
            </div>

            @if(request('unit_id'))
                <a href="{{ route('items.index') }}"
                   class="btn btn-secondary">
                    Filter zurücksetzen
                </a>
            @endif
        </div>
    </div>
</div>

<script>
function itemSearch() {
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
