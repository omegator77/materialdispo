<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Geräte schnell erfassen">
            <x-slot name="actions">
                <a href="{{ route('items.index') }}" class="text-sm text-gray-600 hover:text-gray-900">← Zurück zur Geräteliste</a>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="page"
         x-data="bulkCreate({
            types: @js($types),
            units: @js($units->map(fn ($u) => ['id' => $u->id, 'bezeichnung' => $u->bezeichnung])->values()),
            nextNumbers: @js($nextNumbers),
            parseUrl: @js(route('items.bulk.parse')),
            old: @js(old('rows', []))
         })">

        @if ($errors->any())
            <div class="bg-red-50 border border-red-300 text-red-700 px-4 py-3 rounded text-sm">
                <ul class="list-disc list-inside">
                    @foreach (array_unique($errors->all()) as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        {{-- Text-Eingabe: eine Zeile pro Position --}}
        <div class="card p-4">
            <label for="paste" class="form-label">
                Schnell per Text <span class="font-normal text-gray-400">— eine Position pro Zeile, z. B. <code>3x LDX 86N #21-23</code> oder <code>5 Sachtler</code></span>
            </label>
            <div class="flex flex-col sm:flex-row gap-2">
                <textarea id="paste" x-model="text" rows="2" class="form-control w-full text-sm" placeholder="3x LDX 86N #21-23&#10;5 Stativ"
                          @keydown.ctrl.enter.prevent="parse()" @keydown.meta.enter.prevent="parse()"></textarea>
                <button type="button" @click="parse()" :disabled="!text.trim() || busy"
                        class="btn btn-secondary disabled:opacity-40 whitespace-nowrap">
                    Zeilen erkennen
                </button>
            </div>
            <p class="text-xs text-gray-400 mt-1">Typ und Gruppe werden automatisch erkannt (auch bei Tippfehlern). Strg+Enter übernimmt.</p>
        </div>

        <form method="POST" action="{{ route('items.bulk.store') }}" class="space-y-4">
            @csrf

            <div class="card overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="px-3 py-3">Gerätetyp</th>
                            <th class="px-3 py-3">Gruppe</th>
                            <th class="px-3 py-3">Bezeichnung</th>
                            <th class="px-3 py-3 w-20">Anzahl</th>
                            <th class="px-3 py-3 w-44">Nummern <span class="font-normal text-gray-400">(21-23, 30)</span></th>
                            <th class="px-3 py-3">Vermieter</th>
                            <th class="px-3 py-3 w-64">Mietzeitraum</th>
                            <th class="px-3 py-3 w-10"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(row, i) in rows" :key="row.key">
                            <tr class="border-b align-top" :class="row.hint ? 'bg-amber-50' : ''">
                                <td class="px-3 py-2">
                                    <select :name="`rows[${i}][geraetetyp_id]`" x-model="row.geraetetyp_id" @change="onType(row)" class="form-control w-full text-sm">
                                        <option value="">— frei —</option>
                                        @foreach($units as $u)
                                            @if($types->where('units_id', $u->id)->isNotEmpty())
                                            <optgroup label="{{ $u->bezeichnung }}">
                                                @foreach($types->where('units_id', $u->id) as $t)<option value="{{ $t['id'] }}">{{ $t['bezeichnung'] }}</option>@endforeach
                                            </optgroup>
                                            @endif
                                        @endforeach
                                    </select>
                                    <p x-show="row.hint" x-text="row.hint" class="text-xs text-amber-700 mt-1"></p>
                                </td>
                                <td class="px-3 py-2">
                                    <select :name="`rows[${i}][units_id]`" x-model="row.units_id" @change="suggest(row)" required class="form-control w-full text-sm">
                                        <option value="">Gruppe…</option>
                                        @foreach($units as $u)<option value="{{ $u->id }}">{{ $u->bezeichnung }}</option>@endforeach
                                    </select>
                                </td>
                                <td class="px-3 py-2">
                                    <input type="text" :name="`rows[${i}][bezeichnung]`" x-model="row.bezeichnung" class="form-control w-full text-sm" placeholder="aus Typ">
                                </td>
                                <td class="px-3 py-2">
                                    <input type="number" min="1" max="100" :name="`rows[${i}][anzahl]`" x-model.number="row.anzahl" :disabled="!!row.nummern.trim()"
                                           class="form-control w-full text-sm disabled:bg-gray-100">
                                </td>
                                <td class="px-3 py-2">
                                    <input type="text" :name="`rows[${i}][nummern]`" x-model="row.nummern" class="form-control w-full text-sm"
                                           :placeholder="row.units_id && nextNumbers[row.units_id] ? `ab ${nextNumbers[row.units_id]}` : 'automatisch'">
                                    <p class="text-xs text-gray-400 mt-1" x-text="preview(row)"></p>
                                </td>
                                <td class="px-3 py-2">
                                    <select :name="`rows[${i}][suppliers_id]`" x-model="row.suppliers_id" class="form-control w-full text-sm">
                                        <option value="">Eigentum</option>
                                        @foreach($suppliers as $s)<option value="{{ $s->id }}">{{ $s->bezeichnung }}</option>@endforeach
                                    </select>
                                </td>
                                <td class="px-3 py-2">
                                    <div class="flex gap-1" x-show="row.suppliers_id">
                                        <input type="text" :name="`rows[${i}][rent_start]`" x-model="row.rent_start" class="form-control datepicker w-full text-sm" placeholder="TT.MM.JJJJ" autocomplete="off" :required="!!row.suppliers_id">
                                        <input type="text" :name="`rows[${i}][rent_end]`" x-model="row.rent_end" class="form-control datepicker w-full text-sm" placeholder="TT.MM.JJJJ" autocomplete="off" :required="!!row.suppliers_id">
                                    </div>
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <button type="button" @click="remove(i)" class="text-gray-400 hover:text-red-600 text-lg leading-none" title="Zeile entfernen" aria-label="Zeile entfernen">×</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <button type="button" @click="add()" class="text-sm text-orange-600 hover:text-orange-700 font-medium self-start">+ Weitere Zeile</button>
                <div class="flex items-center gap-4">
                    <span class="text-sm text-gray-500"><strong x-text="total()"></strong> Gerät(e) werden angelegt</span>
                    <button type="submit" :disabled="!total()" class="btn btn-primary disabled:opacity-40">
                        Alle anlegen
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script>
    function bulkCreate(cfg) {
        let seq = 0;
        const blank = () => ({ key: ++seq, geraetetyp_id: '', units_id: '', bezeichnung: '', anzahl: 1, nummern: '', suppliers_id: '', rent_start: '', rent_end: '', hint: '' });
        return {
            types: cfg.types, units: cfg.units, nextNumbers: cfg.nextNumbers,
            rows: (cfg.old && cfg.old.length) ? cfg.old.map(r => Object.assign(blank(), r, { anzahl: r.anzahl || 1, nummern: r.nummern || '' })) : [blank()],
            text: '', busy: false,
            add() { this.rows.push(blank()); },
            remove(i) { this.rows.splice(i, 1); if (!this.rows.length) this.add(); },
            onType(row) {
                const t = this.types.find(t => String(t.id) === String(row.geraetetyp_id));
                if (t) { row.units_id = String(t.units_id); row.bezeichnung = t.bezeichnung; row.hint = ''; }
            },
            suggest() {},
            count(row) {
                const spec = (row.nummern || '').trim();
                if (spec) return this.expand(spec).length;
                return Math.max(0, parseInt(row.anzahl) || 0);
            },
            expand(spec) {
                const out = [];
                spec.split(/[\s,;]+/).filter(Boolean).forEach(p => {
                    const m = p.match(/^(\d+)\s*[-–]\s*(\d+)$/);
                    if (m && +m[2] >= +m[1] && +m[2] - +m[1] < 100) { for (let n = +m[1]; n <= +m[2]; n++) out.push(String(n)); }
                    else out.push(p);
                });
                return [...new Set(out)];
            },
            preview(row) {
                const spec = (row.nummern || '').trim();
                if (spec) { const l = this.expand(spec); return l.length > 1 ? `${l.length} Geräte: ${l[0]} … ${l[l.length - 1]}` : ''; }
                const start = row.units_id ? parseInt(this.nextNumbers[row.units_id]) : NaN;
                const n = this.count(row);
                return !isNaN(start) && n > 0 ? `Nr. ${start}${n > 1 ? '–' + (start + n - 1) : ''}` : '';
            },
            total() { return this.rows.reduce((s, r) => s + (r.units_id ? this.count(r) : 0), 0); },
            async parse() {
                if (!this.text.trim()) return;
                this.busy = true;
                try {
                    const res = await fetch(cfg.parseUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                        body: JSON.stringify({ text: this.text }),
                    });
                    if (!res.ok) throw new Error();
                    const { rows } = await res.json();
                    const parsed = rows.map(r => Object.assign(blank(), r, {
                        geraetetyp_id: r.geraetetyp_id ? String(r.geraetetyp_id) : '',
                        units_id: r.units_id ? String(r.units_id) : '',
                        hint: r.hint || '',
                    }));
                    // Leere Startzeile ersetzen, sonst anhängen.
                    const onlyBlank = this.rows.length === 1 && !this.rows[0].units_id && !this.rows[0].bezeichnung;
                    this.rows = onlyBlank ? parsed : this.rows.concat(parsed);
                    this.text = '';
                    this.$nextTick(() => window.initDatepickers && window.initDatepickers());
                } catch (e) { alert('Der Text konnte nicht gelesen werden.'); }
                this.busy = false;
            },
        };
    }
    </script>
</x-app-layout>
