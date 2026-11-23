{{-- Schnell-Hinzufügen per Text + intelligente Vorschläge --}}
{{-- Erwartet: $production, $suggestions, $similarProduction, $hasVbAnforderungen --}}
<div id="schnelleingabe" class="card card-pad space-y-4 border-orange-300">

    @include('partials._quick-add-box', ['previewUrl' => route('productions.quickAdd.preview', $production), 'storeUrl' => route('productions.quickAdd', $production)])

    @if($hasVbAnforderungen)
    <div class="border-t pt-3">
        <p class="text-sm text-gray-700 mb-2">Das VB-Protokoll enthält Anforderungen für diese Produktion.</p>
        <a href="{{ route('productions.vbVorschlag', $production) }}" class="btn btn-secondary">Packliste aus VB-Protokoll vorschlagen</a>
    </div>
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
                    <input type="checkbox" name="item_id[]" value="{{ $s['item']->id }}" class="form-check" checked>
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

