{{-- Command-Palette: Strg/Cmd+K (oder "/") — suchen, springen, Aktionen starten --}}
@php
    $isUser = Auth::user()->isUser();
    $actions = collect([
        ['Dashboard', route('dashboard'), 'dashboard start übersicht'],
        ['Geräte', route('items.index'), 'geräte inventar liste'],
        ['Produktionen', route('productions.index'), 'produktionen projekte'],
        ['Packen', route('itemprods'), 'packen packliste'],
        ['Timeline', route('timeline.items'), 'timeline zeitstrahl belegung gantt'],
        ['Mietvorgänge', route('mietvorgaenge.index'), 'miete mietvorgänge eingehend'],
        ['Vermietvorgänge', route('vermietvorgaenge.index'), 'verleih vermietvorgänge ausgehend'],
    ]);
    if ($isUser) {
        $actions = $actions->concat([
            ['＋ Neue Produktion', route('productions.create'), 'neu neue produktion anlegen erstellen'],
            ['＋ Geräte schnell erfassen', route('items.bulk.create'), 'neu geräte mehrere schnell erfassen anlegen bulk'],
            ['＋ Neues Gerät', route('items.create'), 'neu neues gerät anlegen erstellen'],
            ['＋ Neuer Mietvorgang', route('mietvorgaenge.create'), 'neu neuer mietvorgang miete anlegen'],
            ['＋ Neuer Vermietvorgang', route('vermietvorgaenge.create'), 'neu neuer vermietvorgang verleih anlegen'],
            ['Protokoll', route('activity-log.index'), 'protokoll log aktivität verlauf'],
        ]);
    }
@endphp
<div x-data="commandPalette({ url: @js(route('search')), actions: @js($actions->map(fn ($a) => ['title' => $a[0], 'url' => $a[1], 'keywords' => $a[2]])->values()) })"
     x-on:open-palette.window="show()"
     x-on:keydown.window="onGlobalKey($event)"
     x-cloak>
    <div x-show="open" class="fixed inset-0 z-50 flex items-start justify-center pt-[12vh] px-4" role="dialog" aria-modal="true" aria-label="Suche und Befehle">
        <div class="absolute inset-0 bg-gray-900/50" @click="close()"></div>

        <div class="relative w-full max-w-xl bg-white rounded-xl shadow-2xl overflow-hidden"
             x-transition.opacity.duration.100ms>
            <div class="flex items-center gap-2 px-4 border-b">
                <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"/></svg>
                <input x-ref="input" x-model="query" @input.debounce.120ms="search()"
                       @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)"
                       @keydown.enter.prevent="go()" @keydown.escape.prevent="close()"
                       type="text" autocomplete="off" placeholder="Gerät, Nummer, Produktion oder Befehl…"
                       class="w-full py-3.5 text-sm border-0 focus:ring-0 focus:outline-none">
                <kbd class="text-[10px] text-gray-400 border rounded px-1.5 py-0.5">Esc</kbd>
            </div>

            <ul class="max-h-80 overflow-y-auto py-1" role="listbox">
                <template x-for="(r, i) in results" :key="r.url + r.title">
                    <li role="option" :aria-selected="i === index">
                        <a :href="r.url" @mouseenter="index = i"
                           :class="i === index ? 'bg-orange-50' : ''"
                           class="flex items-center justify-between gap-3 px-4 py-2 text-sm">
                            <span class="min-w-0">
                                <span class="block text-gray-900 truncate" x-text="r.title"></span>
                                <span class="block text-xs text-gray-500 truncate" x-show="r.subtitle" x-text="r.subtitle"></span>
                            </span>
                            <span class="shrink-0 flex items-center gap-2">
                                <span x-show="r.badge" class="text-[11px] rounded-full px-2 py-0.5 max-w-[11rem] truncate"
                                      :class="r.free ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700'" x-text="r.badge"></span>
                                <span class="text-[11px] text-gray-400" x-text="r.type"></span>
                            </span>
                        </a>
                    </li>
                </template>
                <li x-show="!results.length && !loading" class="px-4 py-6 text-sm text-center text-gray-400">
                    Nichts gefunden<span x-show="query"> für „<span x-text="query"></span>“</span>.
                </li>
            </ul>

            <div class="flex items-center justify-between px-4 py-2 border-t bg-gray-50 text-[11px] text-gray-500">
                <span>↑↓ wählen · ↵ öffnen</span>
                <span>Strg/⌘ + K oder <kbd class="border rounded px-1">/</kbd> zum Öffnen</span>
            </div>
        </div>
    </div>
</div>

<script>
function commandPalette(cfg) {
    return {
        open: false, query: '', results: [], index: 0, loading: false, ctl: null,
        show() {
            this.open = true;
            this.query = '';
            this.index = 0;
            this.results = this.matchActions('');
            this.$nextTick(() => this.$refs.input.focus());
        },
        close() { this.open = false; },
        onGlobalKey(e) {
            const typing = /^(INPUT|TEXTAREA|SELECT)$/.test(document.activeElement?.tagName) || document.activeElement?.isContentEditable;
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); this.open ? this.close() : this.show(); }
            else if (e.key === '/' && !typing && !this.open) { e.preventDefault(); this.show(); }
        },
        matchActions(q) {
            const terms = q.toLowerCase().split(/\s+/).filter(Boolean);
            return cfg.actions
                .filter(a => terms.every(t => (a.title + ' ' + a.keywords).toLowerCase().includes(t)))
                .map(a => ({ type: 'Befehl', title: a.title, subtitle: null, url: a.url, badge: null }));
        },
        async search() {
            const q = this.query.trim();
            this.index = 0;
            if (!q) { this.results = this.matchActions(''); return; }
            const actions = this.matchActions(q).slice(0, 4);
            this.results = actions;
            this.ctl?.abort();
            this.ctl = new AbortController();
            this.loading = true;
            try {
                const res = await fetch(cfg.url + '?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' }, signal: this.ctl.signal });
                if (res.ok) {
                    const { results } = await res.json();
                    this.results = actions.concat(results);
                    // Treffer vor Befehlen, sobald es welche gibt.
                    if (results.length) this.results = results.concat(actions);
                }
            } catch (e) { /* abgebrochen */ }
            this.loading = false;
        },
        move(d) { if (!this.results.length) return; this.index = (this.index + d + this.results.length) % this.results.length; },
        go() { const r = this.results[this.index]; if (r) window.location.href = r.url; },
    };
}
</script>
