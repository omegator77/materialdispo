{{-- Schnell-Hinzufügen per Text + intelligente Vorschläge --}}
{{-- Erwartet: $production, $suggestions, $similarProduction, $openNeeds --}}
<div class="bg-white border border-orange-300 rounded-lg shadow-md p-6 space-y-4">

    @include('partials._quick-add-box', ['previewUrl' => route('productions.quickAdd.preview', $production), 'storeUrl' => route('productions.quickAdd', $production)])

    @if(!empty($cameraTypes) && $cameraTypes->isNotEmpty())
    <form method="POST" action="{{ route('camera-config.auto', $production) }}" class="border-t pt-3">
        @csrf
        <h3 class="text-sm font-semibold text-gray-800 mb-1">Kamerazüge automatisch</h3>
        <p class="text-xs text-gray-500 mb-2">Zubehör (Objektiv, Stativ, Kopf, Adapter) wird aus früheren Zügen mit dieser Kamera gelernt.</p>
        <div class="flex gap-2">
            <input type="number" name="anzahl" value="1" min="1" max="20" class="form-control w-20 text-sm" aria-label="Anzahl">
            <select name="geraetetyp_id" class="form-control flex-1 text-sm" aria-label="Kameratyp">
                @foreach($cameraTypes as $ct)<option value="{{ $ct->id }}">{{ $ct->bezeichnung }}</option>@endforeach
            </select>
            <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white text-sm font-semibold px-3 rounded">Anlegen</button>
        </div>
    </form>
    @endif

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

