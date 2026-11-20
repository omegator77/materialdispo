{{-- Massenaktionen für die in der Tabelle markierten Geräte --}}
{{-- Erwartet: $productions, $mietvorgaenge, $vermietvorgaenge, $units --}}
<div x-data="bulkSelection()" x-cloak x-show="count > 0"
     x-transition.opacity
     class="fixed bottom-4 left-1/2 -translate-x-1/2 z-40 w-11/12 max-w-4xl bg-gray-900 text-white rounded-xl shadow-2xl px-4 py-3">
    <form method="POST" action="{{ route('items.bulk.action') }}" class="flex flex-col md:flex-row md:items-center gap-3">
        @csrf
        <template x-for="id in ids" :key="id"><input type="hidden" name="item_ids[]" :value="id"></template>

        <div class="flex items-center gap-3 shrink-0">
            <span class="font-semibold"><span x-text="count"></span> ausgewählt</span>
            <button type="button" @click="clear()" class="text-xs text-gray-300 hover:text-white underline">Auswahl aufheben</button>
        </div>

        <div class="flex flex-1 flex-col sm:flex-row gap-2">
            <select name="action" x-model="action" class="form-control text-gray-900 text-sm sm:w-44" aria-label="Aktion">
                <option value="production">→ Produktion</option>
                <option value="mietvorgang">→ Mietvorgang</option>
                <option value="vermietvorgang">→ Vermietvorgang</option>
                <option value="unit">→ Gruppe verschieben</option>
            </select>

            <select name="target_id" required class="form-control text-gray-900 text-sm flex-1" aria-label="Ziel">
                <template x-if="action === 'production'">
                    <optgroup label="Produktionen">
                        <option value="" disabled selected>Produktion wählen…</option>
                        @foreach($productions as $p)
                            <option value="{{ $p->id }}">{{ $p->bezeichnung }} ({{ \Carbon\Carbon::parse($p->booking_start)->format('d.m.') }}–{{ \Carbon\Carbon::parse($p->booking_end)->format('d.m.') }})</option>
                        @endforeach
                    </optgroup>
                </template>
                <template x-if="action === 'mietvorgang'">
                    <optgroup label="Mietvorgänge">
                        <option value="" disabled selected>Mietvorgang wählen…</option>
                        @foreach($mietvorgaenge as $mv)
                            <option value="{{ $mv->id }}">{{ $mv->bezeichnung ?? $mv->supplier?->bezeichnung }} ({{ $mv->rent_start->format('d.m.') }}–{{ $mv->rent_end->format('d.m.') }})</option>
                        @endforeach
                    </optgroup>
                </template>
                <template x-if="action === 'vermietvorgang'">
                    <optgroup label="Vermietvorgänge">
                        <option value="" disabled selected>Vermietvorgang wählen…</option>
                        @foreach($vermietvorgaenge as $vv)
                            <option value="{{ $vv->id }}">{{ $vv->bezeichnung ?? $vv->mieter?->bezeichnung }} ({{ $vv->rent_start->format('d.m.') }}–{{ $vv->rent_end->format('d.m.') }})</option>
                        @endforeach
                    </optgroup>
                </template>
                <template x-if="action === 'unit'">
                    <optgroup label="Gruppen">
                        <option value="" disabled selected>Gruppe wählen…</option>
                        @foreach($units as $u)
                            <option value="{{ $u->id }}">{{ $u->bezeichnung }}</option>
                        @endforeach
                    </optgroup>
                </template>
            </select>

            <button type="submit" class="bg-orange-400 hover:bg-orange-500 text-white font-semibold text-sm py-2 px-4 rounded whitespace-nowrap">
                Anwenden
            </button>
        </div>
    </form>
</div>

<script>
function bulkSelection() {
    return {
        ids: [],
        action: 'production',
        lastIndex: null,
        get count() { return this.ids.length; },
        boxes() { return Array.from(document.querySelectorAll('.bulk-select')); },
        init() {
            // Delegiert: funktioniert für alle Tabellenvarianten und nach Filterung.
            document.addEventListener('change', (e) => {
                if (e.target.classList.contains('bulk-select-all')) {
                    this.boxes().forEach(b => { if (!b.closest('tr').classList.contains('hidden')) b.checked = e.target.checked; });
                    this.sync();
                } else if (e.target.classList.contains('bulk-select')) {
                    this.sync();
                }
            });
            document.addEventListener('click', (e) => {
                const box = e.target.closest?.('.bulk-select');
                if (!box) return;
                const visible = this.boxes().filter(b => !b.closest('tr').classList.contains('hidden'));
                const idx = visible.indexOf(box);
                // Shift-Klick wählt den ganzen Bereich seit der letzten Auswahl.
                if (e.shiftKey && this.lastIndex !== null && idx >= 0) {
                    const [from, to] = [Math.min(idx, this.lastIndex), Math.max(idx, this.lastIndex)];
                    visible.slice(from, to + 1).forEach(b => b.checked = box.checked);
                    this.sync();
                }
                this.lastIndex = idx;
            });
        },
        sync() {
            this.ids = this.boxes().filter(b => b.checked).map(b => b.value);
            if (!this.ids.length) document.querySelectorAll('.bulk-select-all').forEach(c => c.checked = false);
        },
        clear() {
            this.boxes().forEach(b => b.checked = false);
            this.sync();
        },
    };
}
</script>
