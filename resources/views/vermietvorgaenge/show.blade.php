<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Vermietvorgang: {{ $vermietvorgang->mieter?->bezeichnung ?? 'Mieter gelöscht' }}
            </h2>

            <a href="{{ route('vermietvorgaenge.index') }}"
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

        @if(session('error'))
        <div class="bg-red-50 border border-red-300 text-red-700 px-4 py-3 rounded">
            {{ session('error') }}
        </div>
        @endif

        <form action="{{ route('vermietvorgaenge.update', $vermietvorgang) }}" method="POST" class="bg-white p-6 border border-gray-300 rounded-lg shadow-md space-y-6">
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
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Mieter &amp; Zeitraum</h3>
                <p class="text-xs text-gray-500 mb-4">Änderungen hier werden auf alle zugeordneten Geräte übertragen.</p>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="mieter_id" class="block text-sm font-medium text-gray-700">Mieter</label>
                        <select name="mieter_id" id="mieter_id" class="form-control w-full" required>
                            @foreach($mieter as $m)
                            <option value="{{ $m->id }}" @selected(old('mieter_id', $vermietvorgang->mieter_id) == $m->id)>
                                {{ $m->bezeichnung }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="rent_start" class="block text-sm font-medium text-gray-700">Verleihbeginn</label>
                        <input type="text" name="rent_start" id="rent_start"
                               value="{{ old('rent_start', \Carbon\Carbon::parse($vermietvorgang->rent_start)->format('d.m.Y')) }}"
                               class="form-control datepicker w-full" placeholder="TT.MM.JJJJ" required>
                    </div>

                    <div>
                        <label for="rent_end" class="block text-sm font-medium text-gray-700">Verleihende</label>
                        <input type="text" name="rent_end" id="rent_end"
                               value="{{ old('rent_end', \Carbon\Carbon::parse($vermietvorgang->rent_end)->format('d.m.Y')) }}"
                               class="form-control datepicker w-full" placeholder="TT.MM.JJJJ" required>
                    </div>
                </div>
            </section>

            <section class="border-t pt-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Transport-Logistik</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="transport_type_start" class="block text-sm font-medium text-gray-700">
                            Hinweg — wie kommt das Gerät zum Mieter?
                        </label>
                        <input type="text" name="transport_type_start" id="transport_type_start" class="form-control w-full" value="{{ old('transport_type_start', $vermietvorgang->transport_type_start) }}">
                    </div>

                    <div>
                        <label for="transport_type_end" class="block text-sm font-medium text-gray-700">
                            Rückweg — wie kommt es zurück?
                        </label>
                        <input type="text" name="transport_type_end" id="transport_type_end" class="form-control w-full" value="{{ old('transport_type_end', $vermietvorgang->transport_type_end) }}">
                    </div>
                </div>

                <label class="flex items-center gap-2 mt-4 text-sm text-gray-700">
                    <input type="checkbox" name="notify_mieter" value="1" @checked(old('notify_mieter', $vermietvorgang->notify_mieter))>
                    Mieter automatisch benachrichtigen
                </label>
            </section>

            <section class="border-t pt-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Erinnerungen</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="reminder_days_before_start" class="block text-sm font-medium text-gray-700">
                            Erinnerung vor Verleihbeginn (Tage)
                        </label>
                        <input type="number" min="0" max="60" name="reminder_days_before_start" id="reminder_days_before_start"
                               class="form-control w-full"
                               placeholder="Standard: {{ config('reminders.default_days_before') }}"
                               value="{{ old('reminder_days_before_start', $vermietvorgang->reminder_days_before_start ?? '') }}">
                    </div>
                    <div>
                        <label for="reminder_days_before_end" class="block text-sm font-medium text-gray-700">
                            Erinnerung vor Verleihende (Tage)
                        </label>
                        <input type="number" min="0" max="60" name="reminder_days_before_end" id="reminder_days_before_end"
                               class="form-control w-full"
                               placeholder="Standard: {{ config('reminders.default_days_before') }}"
                               value="{{ old('reminder_days_before_end', $vermietvorgang->reminder_days_before_end ?? '') }}">
                    </div>
                </div>

                <div class="mt-4">
                    <label for="mailing_list_id" class="block text-sm font-medium text-gray-700">Mailingliste</label>
                    <select name="mailing_list_id" id="mailing_list_id" class="form-control w-full">
                        <option value="">
                            {{ $defaultMailingList ? '— keine (Standardliste: '.$defaultMailingList->name.') —' : '— keine (keine Standardliste festgelegt) —' }}
                        </option>
                        @foreach($mailingLists as $list)
                        <option value="{{ $list->id }}" @selected(old('mailing_list_id', $vermietvorgang->mailing_list_id ?? '') == $list->id)>
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
                @foreach(['start' => 'Hinweg (Verleihbeginn)', 'end' => 'Rückweg (Verleihende)'] as $type => $label)
                @php
                    $actionLabel = $vermietvorgang->transportActionLabel($type);
                    $actionLabelLower = mb_strtolower($actionLabel);
                @endphp
                <div class="border border-gray-200 rounded p-4">
                    <div class="text-sm font-medium text-gray-700 mb-2">{{ $label }}</div>

                    @if($vermietvorgang->isTransportConfirmed($type))
                        @php $confirmedBy = $type === 'start' ? $vermietvorgang->transportStartConfirmedBy : $vermietvorgang->transportEndConfirmedBy; @endphp
                        <p class="text-sm text-green-700 mb-2">
                            ✓ {{ $actionLabel }}
                            @if($confirmedBy) von {{ $confirmedBy->name }} @endif
                            am {{ $vermietvorgang->{"transport_{$type}_confirmed_at"}->format('d.m.Y H:i') }} Uhr
                        </p>
                        <form action="{{ route('vermietvorgaenge.reopenTransport', [$vermietvorgang, $type]) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm text-gray-600 hover:underline">Wieder öffnen</button>
                        </form>
                    @else
                        <p class="text-sm text-gray-500 mb-2">Noch nicht {{ $actionLabelLower }}.</p>
                        <form action="{{ route('vermietvorgaenge.confirmTransport', [$vermietvorgang, $type]) }}" method="POST">
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
                    <div class="text-sm font-medium text-gray-700 mb-2">Gerichtet</div>

                    @if($vermietvorgang->isGerichtet())
                        <p class="text-sm text-green-700 mb-2">
                            ✓ Gerichtet
                            @if($vermietvorgang->gerichtetConfirmedBy) von {{ $vermietvorgang->gerichtetConfirmedBy->name }} @endif
                            am {{ $vermietvorgang->gerichtet_confirmed_at->format('d.m.Y H:i') }} Uhr
                        </p>
                        <form action="{{ route('vermietvorgaenge.reopenGerichtet', $vermietvorgang) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm text-gray-600 hover:underline">Wieder öffnen</button>
                        </form>
                    @else
                        <p class="text-sm text-gray-500 mb-2">Noch nicht gerichtet.</p>
                        <form action="{{ route('vermietvorgaenge.confirmGerichtet', $vermietvorgang) }}" method="POST">
                            @csrf
                            <button type="submit" class="bg-orange-400 hover:bg-orange-500 text-white text-sm font-semibold py-1.5 px-3 rounded">
                                Als gerichtet markieren
                            </button>
                        </form>
                    @endif
                </div>

                <div class="border border-gray-200 rounded p-4">
                    <div class="text-sm font-medium text-gray-700 mb-2">Vollständig zurück</div>

                    @if($vermietvorgang->isVollstaendigZurueck())
                        <p class="text-sm text-green-700 mb-2">
                            ✓ Vollständig zurück
                            @if($vermietvorgang->vollstaendigZurueckConfirmedBy) von {{ $vermietvorgang->vollstaendigZurueckConfirmedBy->name }} @endif
                            am {{ $vermietvorgang->vollstaendig_zurueck_confirmed_at->format('d.m.Y H:i') }} Uhr
                        </p>
                        <form action="{{ route('vermietvorgaenge.reopenVollstaendigZurueck', $vermietvorgang) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm text-gray-600 hover:underline">Wieder öffnen</button>
                        </form>
                    @else
                        <p class="text-sm text-gray-500 mb-2">Noch nicht vollständig zurück.</p>
                        <form action="{{ route('vermietvorgaenge.confirmVollstaendigZurueck', $vermietvorgang) }}" method="POST">
                            @csrf
                            <button type="submit" class="bg-orange-400 hover:bg-orange-500 text-white text-sm font-semibold py-1.5 px-3 rounded">
                                Als vollständig zurück markieren
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        {{-- Zugeordnete Geräte --}}
        <div class="bg-white border border-gray-300 rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Zugeordnete Geräte ({{ $vermietvorgang->items->count() }})</h3>

            <div class="space-y-2 mb-6">
                @forelse($vermietvorgang->items as $item)
                <div class="flex items-center justify-between border border-gray-200 rounded px-4 py-2 text-sm">
                    <div>
                        {{ $item->bezeichnung }} @if($item->nummer)<span class="text-gray-400">({{ $item->nummer }})</span>@endif
                        @if($item->vermietvorgang_manual)
                        <span class="ml-2 inline-block bg-blue-50 text-blue-700 text-xs px-2 py-0.5 rounded-full">Manuell</span>
                        @endif
                    </div>

                    <form action="{{ route('vermietvorgaenge.detachItem', [$vermietvorgang, $item]) }}" method="POST"
                          onsubmit="return confirm('Gerät wirklich entfernen? Mieter und Verleihzeitraum werden am Gerät gelöscht.');">
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
            <form action="{{ route('vermietvorgaenge.attachItems', $vermietvorgang) }}" method="POST"
                  x-data="vermietvorgangItemPicker({
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
        function vermietvorgangItemPicker({ items }) {
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
