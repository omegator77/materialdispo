{{-- Schnell-Hinzufügen per Text + intelligente Vorschläge --}}
{{-- Erwartet: $production, $suggestions, $similarProduction, $openNeeds --}}
<div class="bg-white border border-orange-300 rounded-lg shadow-md p-6 space-y-4"
     x-data="quickAdd({ previewUrl: @js(route('productions.quickAdd.preview', $production)) })">

    <div>
        <h2 class="text-lg font-semibold text-gray-800">Schnell hinzufügen</h2>
        <p class="text-xs text-gray-500">Bedarf einfach tippen — z. B. <code>3x LDX, 2 Stativ, 22er Optik</code>. Freie Geräte werden automatisch gewählt (Eigentum vor Miete).</p>
    </div>

    <form method="POST" action="{{ route('productions.quickAdd', $production) }}" class="space-y-3">
        @csrf
        <textarea name="text" x-model="text" @input.debounce.350ms="preview()" rows="2" required
                  placeholder="3x LDX, 2 Stativ, 4x Funkstrecke" class="form-control w-full text-sm"
                  @keydown.ctrl.enter.prevent="$el.form.submit()" @keydown.meta.enter.prevent="$el.form.submit()"></textarea>

        <template x-if="plan">
            <div class="space-y-2 text-sm">
                <template x-for="line in plan.lines" :key="line.label + line.requested">
                    <div class="rounded border px-3 py-2" :class="line.missing ? 'border-amber-300 bg-amber-50' : 'border-green-200 bg-green-50'">
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-medium text-gray-900">
                                <span x-text="line.requested + '× ' + line.label"></span>
                                <span x-show="!line.matched" class="text-amber-700 font-normal"> — nichts Passendes erkannt</span>
                            </span>
                            <span class="text-xs whitespace-nowrap" :class="line.missing ? 'text-amber-700' : 'text-green-700'"
                                  x-text="line.missing ? (line.items.length + ' von ' + line.requested + ' verfügbar') : '✓ komplett'"></span>
                        </div>
                        <div class="mt-1 flex flex-wrap gap-1" x-show="line.items.length">
                            <template x-for="it in line.items" :key="it.id">
                                <span class="text-xs bg-white border border-gray-200 rounded-full px-2 py-0.5">
                                    <span x-text="it.label"></span><span x-show="it.rented" class="text-amber-600"> · Miete</span>
                                </span>
                            </template>
                        </div>
                        <ul class="mt-1 text-xs text-red-700 space-y-0.5" x-show="line.blocked.length">
                            <template x-for="b in line.blocked" :key="b.id">
                                <li>✗ <span x-text="b.label"></span> — <span x-text="b.reason"></span></li>
                            </template>
                        </ul>
                        <p class="mt-1 text-xs text-gray-600" x-show="line.missing && line.substitutes.length">
                            Alternativen in der Gruppe:
                            <template x-for="s in line.substitutes" :key="s.id"><span class="inline-block bg-white border rounded px-1.5 py-0.5 mr-1" x-text="s.label"></span></template>
                        </p>
                    </div>
                </template>
            </div>
        </template>

        <div class="flex items-center justify-between gap-3">
            <span class="text-xs text-gray-500" x-show="plan" x-text="plan ? (plan.total + ' Gerät(e) bereit' + (plan.missing ? ', ' + plan.missing + ' fehlen' : '')) : ''"></span>
            <button type="submit" :disabled="!plan || !plan.total"
                    class="ml-auto bg-orange-400 hover:bg-orange-500 disabled:opacity-40 text-white font-semibold text-sm py-2 px-4 rounded">
                Hinzufügen
            </button>
        </div>
    </form>

    @if($openNeeds)
    <form method="POST" action="{{ route('productions.autoFill', $production) }}" class="border-t pt-3">
        @csrf
        <p class="text-sm text-gray-700 mb-2">
            Das VB-Protokoll verlangt noch <strong>{{ array_sum(array_column($openNeeds, 'anzahl')) }}</strong> Position(en) mehr.
        </p>
        <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white text-sm font-semibold py-2 px-4 rounded">
            Offenen Bedarf automatisch zuordnen
        </button>
    </form>
    @endif

    @if($suggestions)
    <div class="border-t pt-3">
        <h3 class="text-sm font-semibold text-gray-800 mb-1">Wird oft zusammen gebucht</h3>
        <p class="text-xs text-gray-500 mb-2">Aus früheren Produktionen mit dem hier bereits geplanten Material.</p>
        <form method="POST" action="{{ route('productions.attachItem', $production) }}" class="space-y-2">
            @csrf
            <div class="flex flex-wrap gap-2">
                @foreach($suggestions as $s)
                <label class="inline-flex items-center gap-1.5 text-xs bg-gray-50 border border-gray-200 rounded-full pl-2 pr-3 py-1 cursor-pointer hover:bg-orange-50">
                    <input type="checkbox" name="item_id[]" value="{{ $s['item']->id }}" class="rounded border-gray-300" checked>
                    <span>{{ $s['item']->bezeichnung }}{{ $s['item']->nummer ? ' ('.$s['item']->nummer.')' : '' }}</span>
                    <span class="text-gray-400">{{ $s['together'] }}×</span>
                </label>
                @endforeach
            </div>
            <button type="submit" class="text-sm text-orange-600 hover:text-orange-700 font-medium">Ausgewählte hinzufügen →</button>
        </form>
    </div>
    @endif

    @if($similarProduction)
    <div class="border-t pt-3 text-sm">
        <span class="text-gray-600">Ähnlich zu</span>
        <a href="{{ route('productions.show', $similarProduction) }}" class="font-medium text-gray-900 hover:text-orange-600">{{ $similarProduction->bezeichnung }}</a>:
        <a href="{{ route('productions.importFrom', [$production, $similarProduction]) }}" class="text-orange-600 hover:text-orange-700 font-medium">Material übernehmen →</a>
    </div>
    @endif
</div>

<script>
function quickAdd(cfg) {
    return {
        text: '', plan: null, ctl: null,
        async preview() {
            if (!this.text.trim()) { this.plan = null; return; }
            this.ctl?.abort();
            this.ctl = new AbortController();
            try {
                const res = await fetch(cfg.previewUrl, {
                    method: 'POST', signal: this.ctl.signal,
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: JSON.stringify({ text: this.text }),
                });
                this.plan = res.ok ? await res.json() : null;
            } catch (e) { /* abgebrochen */ }
        },
    };
}
</script>
