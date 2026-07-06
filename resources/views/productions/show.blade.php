<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Materialzuordnung
            </h2>

            <div class="flex items-center gap-2">
                @php
                $vbProtokoll = $production->vbProtokoll;
                $vbAbgleich = $vbProtokoll && $vbProtokoll->anforderungen->isNotEmpty() ? $vbProtokoll->abgleich() : null;
                $vbAbgleichMatched = $vbAbgleich?->filter(fn ($r) => ! is_null($r['erfuellt']));
                @endphp

                @if($vbProtokoll || Auth::user()->isUser())
                <a href="{{ $vbProtokoll ? route('vb-protokoll.show', $production->id) : route('vb-protokoll.create', $production->id) }}"
                    class="inline-flex items-center gap-2 justify-center bg-gray-800 hover:bg-gray-900 text-white font-semibold py-2 px-4 rounded">
                    VB-Protokoll
                    @if($vbAbgleichMatched && $vbAbgleichMatched->isNotEmpty())
                    <span class="text-xs px-1.5 py-0.5 rounded-full {{ $vbAbgleichMatched->every(fn ($r) => $r['erfuellt']) ? 'bg-green-500' : 'bg-yellow-500' }}">
                        {{ $vbAbgleichMatched->filter(fn ($r) => $r['erfuellt'])->count() }}/{{ $vbAbgleichMatched->count() }}
                    </span>
                    @endif
                </a>
                @endif

                <a href="{{ route('productions.pdf', $production->id) }}"
                    class="inline-flex justify-center bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded">
                    PDF exportieren
                </a>

                <a href="{{ route('productions.index') }}"
                    class="inline-flex justify-center bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-4 rounded">
                    Zurück
                </a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl w-11/12 mx-auto mt-6 space-y-6">

        @if(session('success'))
        <div class="bg-green-50 border border-green-300 text-green-700 px-4 py-3 rounded">
            {{ session('success') }}
        </div>
        @endif

        @if(session('error'))
        <div class="bg-red-50 border border-red-300 text-red-700 px-4 py-3 rounded">
            {{ session('error') }}
        </div>
        @endif

        {{-- Produktionskopf --}}
        <div class="bg-white border border-gray-300 rounded-lg shadow-md p-6">
            <h1 class="text-2xl font-bold text-gray-900">
                {{ $production->bezeichnung }}
            </h1>

            <p class="text-gray-600 mt-2">
                {{ $production->booking_start ? \Carbon\Carbon::parse($production->booking_start)->format('d.m.Y') : '—' }}
                –
                {{ $production->booking_end ? \Carbon\Carbon::parse($production->booking_end)->format('d.m.Y') : '—' }}
            </p>

            @if(!empty($production->packlist_notes))
            <div class="mt-2 text-sm text-yellow-900 bg-yellow-50 border-l-4 border-yellow-300 px-3 py-2 rounded">
                <strong>Packlisten-Notiz:</strong>
                <span class="whitespace-pre-line">{{ $production->packlist_notes }}</span>
            </div>
            @endif
        </div>

        {{-- Mietvorgänge: read-only Zusammenfassung, Bearbeitung läuft über die eigenständige Mietvorgang-Seite --}}
        @if($mietvorgaenge->isNotEmpty())
        <div class="bg-white border border-gray-300 rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Mietvorgänge (Transport)</h3>

            <div class="space-y-3">
                @foreach($mietvorgaenge as $mietvorgang)
                <div class="border border-gray-200 rounded p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="text-sm text-gray-600">
                        <strong class="text-gray-900">{{ $mietvorgang->supplier?->bezeichnung ?? 'Vermieter gelöscht' }}</strong>
                        &middot; {{ \Carbon\Carbon::parse($mietvorgang->rent_start)->format('d.m.Y') }}
                        – {{ \Carbon\Carbon::parse($mietvorgang->rent_end)->format('d.m.Y') }}
                        &middot; {{ $production->items->where('mietvorgang_id', $mietvorgang->id)->pluck('bezeichnung')->implode(', ') }}

                        <div class="mt-1 flex flex-wrap gap-2">
                            <span class="inline-block bg-gray-100 text-gray-700 text-xs px-2 py-0.5 rounded-full">
                                Hinweg: {{ \App\Models\Mietvorgang::TRANSPORT_TYPES_START[$mietvorgang->transport_type_start] ?? 'offen' }}
                            </span>
                            <span class="inline-block bg-gray-100 text-gray-700 text-xs px-2 py-0.5 rounded-full">
                                Rückweg: {{ \App\Models\Mietvorgang::TRANSPORT_TYPES_END[$mietvorgang->transport_type_end] ?? 'offen' }}
                            </span>
                            @if($mietvorgang->notify_supplier)
                            <span class="inline-block bg-blue-50 text-blue-700 text-xs px-2 py-0.5 rounded-full">
                                Lieferant wird benachrichtigt
                            </span>
                            @endif
                        </div>
                    </div>

                    <a href="{{ route('mietvorgaenge.show', $mietvorgang) }}"
                       class="shrink-0 text-sm text-orange-600 hover:text-orange-700 font-medium">
                        Bearbeiten →
                    </a>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Dry Hire: eigenes Material geht an einen Kunden raus --}}
        @if($production->is_dry_hire)
        @php $dryHire = $production->dryHire; @endphp
        <div class="bg-white border border-gray-300 rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Dry Hire</h3>

            <form action="{{ route('dry-hire.update', $production) }}" method="POST" class="space-y-4">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Lieferung — wie kommt das Gerät zum Kunden?</label>
                        <select name="delivery_type" class="form-control w-full">
                            <option value="">— wählen —</option>
                            @foreach(\App\Models\DryHire::DELIVERY_TYPES as $value => $label)
                            <option value="{{ $value }}" @selected(old('delivery_type', $dryHire?->delivery_type) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Rückgabe — wie kommt es zurück?</label>
                        <select name="return_type" class="form-control w-full">
                            <option value="">— wählen —</option>
                            @foreach(\App\Models\DryHire::RETURN_TYPES as $value => $label)
                            <option value="{{ $value }}" @selected(old('return_type', $dryHire?->return_type) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Kunden-E-Mail</label>
                        <input type="email" name="customer_email" class="form-control w-full"
                               value="{{ old('customer_email', $dryHire?->customer_email) }}">
                    </div>
                    <div class="flex items-end">
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="notify_customer" value="1" @checked(old('notify_customer', $dryHire?->notify_customer))>
                            Kunde automatisch benachrichtigen
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Erinnerung vor Lieferung (Tage)</label>
                        <input type="number" min="0" max="60" name="reminder_days_before_start" class="form-control w-full"
                               placeholder="Standard: {{ config('reminders.default_days_before') }}"
                               value="{{ old('reminder_days_before_start', $dryHire?->reminder_days_before_start) }}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Erinnerung vor Rückgabe (Tage)</label>
                        <input type="number" min="0" max="60" name="reminder_days_before_end" class="form-control w-full"
                               placeholder="Standard: {{ config('reminders.default_days_before') }}"
                               value="{{ old('reminder_days_before_end', $dryHire?->reminder_days_before_end) }}">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Mailingliste</label>
                    <select name="mailing_list_id" class="form-control w-full">
                        <option value="">— keine (nur Standard-Mailingliste, falls konfiguriert) —</option>
                        @foreach($mailingLists as $list)
                        <option value="{{ $list->id }}" @selected(old('mailing_list_id', $dryHire?->mailing_list_id) == $list->id)>{{ $list->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="bg-orange-400 hover:bg-orange-500 text-white font-semibold py-2 px-4 rounded">
                        Speichern
                    </button>
                </div>
            </form>

            @if($dryHire)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6 pt-6 border-t">
                @foreach(['start' => 'Übergabe an Kunde', 'end' => 'Rückgabe vom Kunden'] as $type => $label)
                <div class="border border-gray-200 rounded p-4">
                    <div class="text-sm font-medium text-gray-700 mb-2">{{ $label }}</div>

                    @if($dryHire->isTransportConfirmed($type))
                        @php $confirmedBy = $type === 'start' ? $dryHire->startConfirmedBy : $dryHire->endConfirmedBy; @endphp
                        <p class="text-sm text-green-700 mb-2">
                            ✓ Geklärt
                            @if($confirmedBy) von {{ $confirmedBy->name }} @endif
                            am {{ $dryHire->{"{$type}_confirmed_at"}->format('d.m.Y H:i') }} Uhr
                        </p>
                        <form action="{{ route('dry-hire.reopenTransport', [$production, $type]) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm text-gray-600 hover:underline">Wieder öffnen</button>
                        </form>
                    @else
                        <p class="text-sm text-gray-500 mb-2">Noch nicht geklärt.</p>
                        <form action="{{ route('dry-hire.confirmTransport', [$production, $type]) }}" method="POST">
                            @csrf
                            <button type="submit" class="bg-orange-400 hover:bg-orange-500 text-white text-sm font-semibold py-1.5 px-3 rounded">
                                Als geklärt markieren
                            </button>
                        </form>
                    @endif
                </div>
                @endforeach
            </div>
            @endif
        </div>
        @endif

        {{-- VB-Protokoll Abgleich: direkt hier sichtbar, kein Wechsel zum Protokoll nötig --}}
        @if($vbAbgleich && $vbAbgleich->isNotEmpty())
        @php
        $offenRows = $vbAbgleich->filter(fn ($r) => $r['erfuellt'] !== true);
        $erfuelltRows = $vbAbgleich->filter(fn ($r) => $r['erfuellt'] === true);
        @endphp
        <div class="bg-white border border-gray-300 rounded-lg shadow-md p-6" x-data="{ showAll: false }">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-lg font-semibold text-gray-800">Abgleich mit VB-Protokoll</h2>
                <a href="{{ route('vb-protokoll.show', $production->id) }}" class="text-sm text-orange-600 hover:text-orange-700">
                    Vollständiges Protokoll →
                </a>
            </div>

            @if($offenRows->isEmpty())
            <p class="text-sm text-green-700">✓ Alle Anforderungen aus dem VB-Protokoll sind erfüllt.</p>
            @else
            <ul class="divide-y divide-gray-100 text-sm">
                @foreach($offenRows as $row)
                <li class="flex items-center justify-between py-1.5">
                    <span class="text-gray-900">{{ $row['label'] }}</span>
                    @if($row['erfuellt'] === false)
                    <span class="text-red-700 font-medium text-xs whitespace-nowrap">fehlt {{ $row['benoetigt'] - $row['gepackt'] }}</span>
                    @else
                    <span class="text-gray-400 text-xs whitespace-nowrap">manuell prüfen</span>
                    @endif
                </li>
                @endforeach
            </ul>
            @endif

            @if($erfuelltRows->isNotEmpty())
            <button type="button" @click="showAll = !showAll" class="mt-3 text-xs text-gray-500 hover:text-gray-700">
                <span x-show="!showAll">{{ $erfuelltRows->count() }} erfüllte Anforderung(en) anzeigen</span>
                <span x-show="showAll" x-cloak>Erfüllte ausblenden</span>
            </button>

            <ul class="mt-2 divide-y divide-gray-100 text-sm" x-show="showAll" x-cloak>
                @foreach($erfuelltRows as $row)
                <li class="flex items-center justify-between py-1.5 text-gray-500">
                    <span>{{ $row['label'] }}</span>
                    <span class="text-xs whitespace-nowrap">✓ {{ $row['gepackt'] }}/{{ $row['benoetigt'] }}</span>
                </li>
                @endforeach
            </ul>
            @endif
        </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

            {{-- Links: Material hinzufügen --}}
            @if(Auth::user()->isUser())
            <div class="lg:col-span-2">
                <div class="bg-white border border-gray-300 rounded-lg shadow-md p-6 space-y-6">
                    <h2 class="text-lg font-semibold text-gray-800">
                        Material hinzufügen
                    </h2>

                    <form method="GET" action="{{ route('productions.show', $production->id) }}" class="space-y-4">
                        <div>
                            <label for="unit" class="block text-sm font-medium text-gray-700 mb-1">
                                Gruppe filtern
                            </label>

                            <select name="unit"
                                id="unit"
                                class="form-control w-full"
                                onchange="this.form.submit()">
                                <option value="">Alle Gruppen</option>

                                @foreach ($allUnits as $unit)
                                <option value="{{ $unit->id }}" {{ request('unit') == $unit->id ? 'selected' : '' }}>
                                    {{ $unit->bezeichnung }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox"
                                name="show_unavailable"
                                value="1"
                                class="rounded border-gray-300"
                                onchange="this.form.submit()"
                                {{ request('show_unavailable') ? 'checked' : '' }}>
                            Nicht verfügbare Geräte anzeigen
                        </label>
                    </form>

                    <form id="item-selection-form"
                        method="POST"
                        action="{{ route('productions.attachItem', $production->id) }}"
                        class="space-y-4"
                        x-data="itemPicker({
                            items: [
                                @foreach ($availableItems as $item)
                                { id: {{ $item->id }}, unitId: '{{ $item->units_id }}', available: {{ $item->is_available ? 'true' : 'false' }}, label: @js($item->bezeichnung . ($item->nummer ? ' (' . $item->nummer . ')' : '')) },
                                @endforeach
                            ]
                        })">
                        @csrf

                        <input type="hidden" name="unit" value="{{ request('unit') }}">
                        <input type="hidden" name="show_unavailable" value="{{ request('show_unavailable') }}">

                        <div>
                            <label for="item_search" class="block text-sm font-medium text-gray-700 mb-1">
                                Items auswählen
                            </label>

                            <template x-for="item in selected" :key="item.id">
                                <input type="hidden" name="item_id[]" :value="item.id">
                            </template>

                            <div class="flex flex-wrap gap-2 mb-2" x-show="selected.length">
                                <template x-for="item in selected" :key="item.id">
                                    <span class="inline-flex items-center gap-1 bg-orange-50 text-orange-700 text-xs font-medium pl-2 pr-1 py-1 rounded-full border border-orange-200">
                                        <span x-text="item.label"></span>
                                        <button type="button" @click="remove(item)" class="hover:text-orange-900 leading-none px-1">×</button>
                                    </span>
                                </template>
                            </div>

                            <div class="relative">
                                <input type="text" id="item_search"
                                       x-model="query"
                                       @input="open = true"
                                       @focus="open = true"
                                       @click.outside="open = false"
                                       placeholder="Item suchen…"
                                       autocomplete="off"
                                       class="form-control w-full">

                                <div x-show="open" x-cloak
                                     class="absolute z-10 mt-1 w-full max-h-64 overflow-y-auto bg-white border border-gray-200 rounded-lg shadow-lg">
                                    <template x-for="item in filteredItems" :key="item.id">
                                        <button type="button"
                                                @click="toggle(item)"
                                                :disabled="!item.available"
                                                :class="!item.available ? 'text-gray-300 cursor-not-allowed' : (isSelected(item) ? 'bg-orange-50 text-orange-700' : 'hover:bg-orange-50 hover:text-orange-700 text-gray-700')"
                                                class="w-full text-left px-3 py-2 text-sm border-b last:border-0 border-gray-50 flex items-center justify-between gap-2">
                                            <span>
                                                <span x-text="item.label"></span>
                                                <span x-show="!item.available" class="text-xs"> (nicht verfügbar)</span>
                                            </span>
                                            <span x-show="isSelected(item)" class="text-orange-600">✓</span>
                                        </button>
                                    </template>
                                    <div x-show="filteredItems.length === 0" class="px-3 py-2 text-sm text-gray-400">
                                        Keine Treffer.
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4">
                                <label for="notes" class="block text-sm font-medium text-gray-700">
                                    Notiz zum Gerät optional
                                </label>

                                <textarea
                                    name="notes"
                                    id="notes"
                                    rows="2"
                                    class="mt-1 block w-full rounded border-gray-300"
                                    placeholder="z. B. ohne Netzteil, defekt, bleibt im Ü-Wagen...">{{ old('notes') }}</textarea>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-2">
                            <button type="submit"
                                id="add-button"
                                :disabled="selected.length === 0"
                                :class="selected.length === 0 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-orange-500'"
                                class="flex-1 bg-orange-400 text-white font-semibold py-2 px-4 rounded"
                                x-text="selected.length > 1 ? selected.length + ' Geräte hinzufügen' : 'Standard hinzufügen'">
                            </button>

                            <a href="#"
                                id="config-button"
                                @click="if (!configEnabled) $event.preventDefault()"
                                :href="configEnabled ? configUrl : '#'"
                                :class="configEnabled ? 'bg-gray-800 hover:bg-gray-900 cursor-pointer' : 'bg-gray-300 cursor-not-allowed pointer-events-none'"
                                x-show="selected.length"
                                class="flex-1 text-center text-white font-semibold py-2 px-4 rounded">
                                Konfigurieren
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            @endif

            {{-- Rechts: Gepacktes Material --}}
            <div class="{{ Auth::user()->isUser() ? 'lg:col-span-3' : 'lg:col-span-5' }} space-y-6">

                {{-- Einzelmaterial gruppiert --}}
                <div class="bg-white border border-gray-300 rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">
                        Gepacktes Einzelmaterial
                    </h2>

                    @php
                    $groupedItems = $production->items->groupBy(fn($item) => $item->unit->bezeichnung ?? 'Ohne Gruppe');

                    $label = function ($it) {
                    if (! $it) {
                    return '—';
                    }

                    return $it->bezeichnung . ($it->nummer ? ' (' . $it->nummer . ')' : '');
                    };
                    @endphp

                    @forelse($groupedItems as $unitName => $items)
                    <div class="mb-5 last:mb-0">
                        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-2">
                            {{ $unitName }}
                        </h3>

                        <div class="space-y-2">
                            @foreach($items as $item)
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border border-gray-200 rounded-lg p-3 bg-gray-50">
                                <div>
                                    <a href="{{ route('items.show', $item->id) }}"
                                        class="font-semibold text-gray-900 hover:text-orange-500">
                                        {{ $label($item) }}
                                    </a>

                                    @if($item->supplier)
                                    <p class="text-sm text-gray-500">
                                        Mietmaterial: {{ $item->supplier->bezeichnung }}
                                    </p>
                                    @endif

                                    @if(!empty($item->pivot->notes))
                                    <div class="mt-1 text-sm text-gray-600">
                                        <strong>Notiz:</strong> {{ $item->pivot->notes }}
                                    </div>
                                    @endif
                                </div>

                                @if(Auth::user()->isUser())
                                <form action="{{ route('productions.detachItem', [$production->id, $item->id]) }}"
                                    method="POST"
                                    onsubmit="return confirm('Dieses Material wirklich aus der Produktion entfernen?');">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit"
                                        class="w-full sm:w-auto bg-red-600 hover:bg-red-700 text-white font-semibold py-1 px-3 rounded">
                                        Entfernen
                                    </button>
                                </form>
                                @endif
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @empty
                    <p class="text-gray-500">
                        Noch kein Einzelmaterial gepackt.
                    </p>
                    @endforelse
                </div>

                {{-- Kamera-Konfigurationen --}}
                <div class="bg-white border border-gray-300 rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">
                        Kamera-Konfigurationen
                    </h2>

                    <div class="space-y-3">
                        @forelse($production->cameraConfigs as $config)
                        <div class="border border-gray-200 rounded-lg bg-gray-50">
                            <details class="group">
                                <summary class="flex items-center justify-between gap-3 cursor-pointer select-none p-4">
                                    <div class="min-w-0">
                                        <div class="text-sm text-gray-500">
                                            Kamera-Konfiguration {{ $config->cam_number ?? '—' }}
                                        </div>

                                        <div class="font-semibold truncate text-gray-900">
                                            {{ $label($config->item ?? null) }}
                                        </div>
                                    </div>

                                    <svg class="h-5 w-5 text-gray-400 group-open:rotate-180 transition-transform"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                        aria-hidden="true">
                                        <path fill-rule="evenodd"
                                            d="M5.23 7.21a.75.75 0 011.06.02L10 11.172l3.71-3.94a.75.75 0 011.08 1.04l-4.24 4.5a.75.75 0 01-1.08 0l-4.24-4.5a.75.75 0 01.02-1.06z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </summary>

                                <div class="px-4 pb-4">
                                    @php
                                    $configRows = [
                                    'Kamera' => $config->item ?? null,
                                    'Objektiv' => $config->lensItem ?? null,
                                    'Adapter' => $config->adapterItem ?? null,
                                    'Stativ' => $config->tripodItem ?? null,
                                    'Stativkopf' => $config->headItem ?? null,
                                    ];
                                    @endphp

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                                        @foreach($configRows as $labelName => $configItem)
                                        @if($configItem)
                                        <div>
                                            <span class="block text-gray-500">{{ $labelName }}</span>
                                            <span class="font-medium">{{ $label($configItem) }}</span>
                                        </div>
                                        @endif
                                        @endforeach

                                        @if(!empty($config->notes))
                                        <div class="md:col-span-2">
                                            <span class="block text-gray-500">Notiz</span>
                                            <span class="font-medium whitespace-pre-line">{{ $config->notes }}</span>
                                        </div>
                                        @endif
                                    </div>

                                    @if(Auth::user()->isUser())
                                    <div class="mt-4 flex flex-col sm:flex-row sm:justify-end gap-2">
                                        <a href="{{ route('camera-config.edit', $config->id) }}"
                                            class="text-center bg-orange-400 hover:bg-orange-500 text-white font-semibold py-2 px-4 rounded">
                                            Bearbeiten
                                        </a>

                                        <form action="{{ route('camera-config.destroy', $config->id) }}"
                                            method="POST"
                                            onsubmit="return confirm('Diese Kamera-Konfiguration wirklich entfernen?');">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit"
                                                class="w-full sm:w-auto bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded">
                                                Entfernen
                                            </button>
                                        </form>
                                    </div>
                                    @endif
                                </div>
                            </details>
                        </div>
                        @empty
                        <p class="text-gray-500">
                            Noch keine Kamera-Konfigurationen vorhanden.
                        </p>
                        @endforelse
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        function itemPicker({ items }) {
            return {
                items,
                query: '',
                open: false,
                selected: [],
                get filteredItems() {
                    const needle = this.query.trim().toLowerCase();
                    if (needle === '') return this.items;
                    return this.items.filter(item => item.label.toLowerCase().includes(needle));
                },
                isSelected(item) {
                    return this.selected.some(i => i.id === item.id);
                },
                toggle(item) {
                    if (!item.available) return;
                    if (this.isSelected(item)) {
                        this.remove(item);
                    } else {
                        this.selected.push(item);
                    }
                    this.query = '';
                },
                remove(item) {
                    this.selected = this.selected.filter(i => i.id !== item.id);
                },
                get configEnabled() {
                    return this.selected.length === 1 && this.selected[0].unitId === '1';
                },
                get configUrl() {
                    if (!this.configEnabled) return '#';
                    return "{{ route('camera-config.create', $production->id) }}" + "?camera_item_id=" + this.selected[0].id;
                }
            };
        }
    </script>
</x-app-layout>