<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Mietvorgang: {{ $mietvorgang->supplier?->bezeichnung ?? 'Vermieter gelöscht' }}
            </h2>

            <a href="{{ route('mietvorgaenge.index') }}"
               class="inline-flex justify-center bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-4 rounded">
                Zurück
            </a>
        </div>
    </x-slot>

    <div class="max-w-5xl w-11/12 mx-auto mt-6 space-y-6">

        @if(session('success'))
        <div class="bg-green-50 border border-green-300 text-green-700 px-4 py-3 rounded">
            {{ session('success') }}
        </div>
        @endif

        <form action="{{ route('mietvorgaenge.update', $mietvorgang) }}" method="POST" class="bg-white p-6 border border-gray-300 rounded-lg shadow-md space-y-6">
            @csrf
            @method('PUT')

            @if ($errors->any())
                <div class="bg-red-50 border border-red-300 text-red-700 px-4 py-3 rounded">
                    <ul class="list-disc list-inside text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section>
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Vermieter &amp; Zeitraum</h3>
                <p class="text-xs text-gray-500 mb-4">Änderungen hier werden auf alle zugeordneten Geräte übertragen.</p>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="suppliers_id" class="block text-sm font-medium text-gray-700">Vermieter</label>
                        <select name="suppliers_id" id="suppliers_id" class="form-control w-full" required>
                            @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected(old('suppliers_id', $mietvorgang->suppliers_id) == $supplier->id)>
                                {{ $supplier->bezeichnung }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="rent_start" class="block text-sm font-medium text-gray-700">Mietbeginn</label>
                        <input type="text" name="rent_start" id="rent_start"
                               value="{{ old('rent_start', \Carbon\Carbon::parse($mietvorgang->rent_start)->format('d.m.Y')) }}"
                               class="form-control datepicker w-full" placeholder="TT.MM.JJJJ" required>
                    </div>

                    <div>
                        <label for="rent_end" class="block text-sm font-medium text-gray-700">Mietende</label>
                        <input type="text" name="rent_end" id="rent_end"
                               value="{{ old('rent_end', \Carbon\Carbon::parse($mietvorgang->rent_end)->format('d.m.Y')) }}"
                               class="form-control datepicker w-full" placeholder="TT.MM.JJJJ" required>
                    </div>
                </div>
            </section>

            <section class="border-t pt-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Transport-Logistik</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="transport_type_start" class="block text-sm font-medium text-gray-700">
                            Hinweg — wie kommt das Gerät zu uns?
                        </label>
                        <input type="text" name="transport_type_start" id="transport_type_start" class="form-control w-full" value="{{ old('transport_type_start', $mietvorgang->transport_type_start) }}">
                    </div>

                    <div>
                        <label for="transport_type_end" class="block text-sm font-medium text-gray-700">
                            Rückweg — wie geht es zurück?
                        </label>
                        <input type="text" name="transport_type_end" id="transport_type_end" class="form-control w-full" value="{{ old('transport_type_end', $mietvorgang->transport_type_end) }}">
                    </div>
                </div>

                <label class="flex items-center gap-2 mt-4 text-sm text-gray-700">
                    <input type="checkbox" name="notify_supplier" value="1" @checked(old('notify_supplier', $mietvorgang->notify_supplier))>
                    Lieferant automatisch benachrichtigen
                </label>
            </section>

            <section class="border-t pt-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Erinnerungen</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="reminder_days_before_start" class="block text-sm font-medium text-gray-700">
                            Erinnerung vor Mietbeginn (Tage)
                        </label>
                        <input type="number" min="0" max="60" name="reminder_days_before_start" id="reminder_days_before_start"
                               class="form-control w-full"
                               placeholder="Standard: {{ config('reminders.default_days_before') }}"
                               value="{{ old('reminder_days_before_start', $mietvorgang->reminder_days_before_start ?? '') }}">
                    </div>
                    <div>
                        <label for="reminder_days_before_end" class="block text-sm font-medium text-gray-700">
                            Erinnerung vor Mietende (Tage)
                        </label>
                        <input type="number" min="0" max="60" name="reminder_days_before_end" id="reminder_days_before_end"
                               class="form-control w-full"
                               placeholder="Standard: {{ config('reminders.default_days_before') }}"
                               value="{{ old('reminder_days_before_end', $mietvorgang->reminder_days_before_end ?? '') }}">
                    </div>
                </div>

                <div class="mt-4">
                    <label for="mailing_list_id" class="block text-sm font-medium text-gray-700">Mailingliste</label>
                    <select name="mailing_list_id" id="mailing_list_id" class="form-control w-full">
                        <option value="">
                            {{ $defaultMailingList ? '— keine (Standardliste: '.$defaultMailingList->name.') —' : '— keine (keine Standardliste festgelegt) —' }}
                        </option>
                        @foreach($mailingLists as $list)
                        <option value="{{ $list->id }}" @selected(old('mailing_list_id', $mietvorgang->mailing_list_id ?? '') == $list->id)>
                            {{ $list->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
            </section>

            <div class="border-t pt-6 flex justify-end">
                <button type="submit"
                        class="inline-flex justify-center bg-orange-400 hover:bg-orange-500 text-white font-semibold py-2 px-4 rounded">
                    Speichern
                </button>
            </div>
        </form>

        {{-- Transport-Status --}}
        <div class="bg-white border border-gray-300 rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Transport-Status</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach(['start' => 'Hinweg (Mietbeginn)', 'end' => 'Rückweg (Mietende)'] as $type => $label)
                @php
                    $actionLabel = $mietvorgang->transportActionLabel($type);
                    $actionLabelLower = mb_strtolower($actionLabel);
                @endphp
                <div class="border border-gray-200 rounded p-4">
                    <div class="text-sm font-medium text-gray-700 mb-2">{{ $label }}</div>

                    @if($mietvorgang->isTransportConfirmed($type))
                        @php $confirmedBy = $type === 'start' ? $mietvorgang->transportStartConfirmedBy : $mietvorgang->transportEndConfirmedBy; @endphp
                        <p class="text-sm text-green-700 mb-2">
                            ✓ {{ $actionLabel }}
                            @if($confirmedBy) von {{ $confirmedBy->name }} @endif
                            am {{ $mietvorgang->{"transport_{$type}_confirmed_at"}->format('d.m.Y H:i') }} Uhr
                        </p>
                        <form action="{{ route('mietvorgaenge.reopenTransport', [$mietvorgang, $type]) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm text-gray-600 hover:underline">Wieder öffnen</button>
                        </form>
                    @else
                        <p class="text-sm text-gray-500 mb-2">Noch nicht {{ $actionLabelLower }}.</p>
                        <form action="{{ route('mietvorgaenge.confirmTransport', [$mietvorgang, $type]) }}" method="POST">
                            @csrf
                            <button type="submit" class="bg-orange-400 hover:bg-orange-500 text-white text-sm font-semibold py-1.5 px-3 rounded">
                                Als {{ $actionLabelLower }} markieren
                            </button>
                        </form>
                    @endif
                </div>
                @endforeach
            </div>
        </div>

        {{-- Material-Status --}}
        <div class="bg-white border border-gray-300 rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Material-Status</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="border border-gray-200 rounded p-4">
                    <div class="text-sm font-medium text-gray-700 mb-2">Entgegengenommen und kontrolliert</div>

                    @if($mietvorgang->isKontrolliert())
                        <p class="text-sm text-green-700 mb-2">
                            ✓ Entgegengenommen und kontrolliert
                            @if($mietvorgang->kontrolliertConfirmedBy) von {{ $mietvorgang->kontrolliertConfirmedBy->name }} @endif
                            am {{ $mietvorgang->kontrolliert_confirmed_at->format('d.m.Y H:i') }} Uhr
                        </p>
                        <form action="{{ route('mietvorgaenge.reopenKontrolliert', $mietvorgang) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm text-gray-600 hover:underline">Wieder öffnen</button>
                        </form>
                    @else
                        <p class="text-sm text-gray-500 mb-2">Noch nicht entgegengenommen/kontrolliert.</p>
                        <form action="{{ route('mietvorgaenge.confirmKontrolliert', $mietvorgang) }}" method="POST">
                            @csrf
                            <button type="submit" class="bg-orange-400 hover:bg-orange-500 text-white text-sm font-semibold py-1.5 px-3 rounded">
                                Als entgegengenommen und kontrolliert markieren
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        {{-- Zugeordnete Geräte --}}
        <div class="bg-white border border-gray-300 rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Zugeordnete Geräte ({{ $mietvorgang->items->count() }})</h3>

            <div class="space-y-2 mb-6">
                @forelse($mietvorgang->items as $item)
                <div class="flex items-center justify-between border border-gray-200 rounded px-4 py-2 text-sm">
                    <div>
                        {{ $item->bezeichnung }} @if($item->nummer)<span class="text-gray-400">({{ $item->nummer }})</span>@endif
                        @if($item->mietvorgang_manual)
                        <span class="ml-2 inline-block bg-blue-50 text-blue-700 text-xs px-2 py-0.5 rounded-full">Manuell</span>
                        @endif
                    </div>

                    <form action="{{ route('mietvorgaenge.detachItem', [$mietvorgang, $item]) }}" method="POST"
                          onsubmit="return confirm('Gerät wirklich entfernen? Vermieter und Mietzeitraum werden am Gerät gelöscht.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:underline">
                            Entfernen
                        </button>
                    </form>
                </div>
                @empty
                <p class="text-sm text-gray-500">Noch keine Geräte zugeordnet.</p>
                @endforelse
            </div>

            {{-- Geräte-Picker --}}
            <form action="{{ route('mietvorgaenge.attachItems', $mietvorgang) }}" method="POST"
                  x-data="mietvorgangItemPicker({
                      items: [
                          @foreach($assignableItems as $item)
                          { id: {{ $item->id }}, label: @js($item->bezeichnung . ($item->nummer ? ' (' . $item->nummer . ')' : '')) },
                          @endforeach
                      ]
                  })">
                @csrf

                <label for="item_search" class="block text-sm font-medium text-gray-700 mb-1">Geräte hinzufügen</label>

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
                           @focus="open = true"
                           @click.outside="open = false"
                           placeholder="Gerät suchen…"
                           autocomplete="off"
                           class="form-control w-full">

                    <div x-show="open" x-cloak
                         class="absolute z-10 mt-1 w-full max-h-64 overflow-y-auto bg-white border border-gray-200 rounded-lg shadow-lg">
                        <template x-for="item in filteredItems" :key="item.id">
                            <button type="button"
                                    @click="toggle(item)"
                                    class="w-full text-left px-3 py-2 text-sm border-b last:border-0 border-gray-50 flex items-center justify-between gap-2 hover:bg-orange-50 hover:text-orange-700 text-gray-700">
                                <span x-text="item.label"></span>
                                <span x-show="isSelected(item)" class="text-orange-600">✓</span>
                            </button>
                        </template>
                        <div x-show="filteredItems.length === 0" class="px-3 py-2 text-sm text-gray-400">
                            Keine Treffer.
                        </div>
                    </div>
                </div>

                <button type="submit"
                        :disabled="selected.length === 0"
                        :class="selected.length === 0 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-orange-500'"
                        class="mt-4 bg-orange-400 text-white font-semibold py-2 px-4 rounded"
                        x-text="selected.length > 1 ? selected.length + ' Geräte hinzufügen' : 'Gerät hinzufügen'">
                </button>
            </form>
        </div>
    </div>

    <script>
        function mietvorgangItemPicker({ items }) {
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
                    if (this.isSelected(item)) {
                        this.remove(item);
                    } else {
                        this.selected.push(item);
                    }
                    this.query = '';
                },
                remove(item) {
                    this.selected = this.selected.filter(i => i.id !== item.id);
                }
            };
        }
    </script>
</x-app-layout>
