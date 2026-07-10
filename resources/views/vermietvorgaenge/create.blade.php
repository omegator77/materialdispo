<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Neuen Vermietvorgang anlegen
        </h2>
    </x-slot>

    <div class="max-w-3xl w-11/12 mx-auto mt-6">
        <form action="{{ route('vermietvorgaenge.store') }}" method="POST"
              class="bg-white p-6 border border-gray-300 rounded-lg shadow-md space-y-6"
              x-data="vermietvorgangItemPicker({
                  items: [
                      @foreach($assignableItems as $item)
                      { id: {{ $item->id }}, label: @js($item->bezeichnung . ($item->nummer ? ' (' . $item->nummer . ')' : '')) },
                      @endforeach
                  ]
              })"
              @submit="if (selected.length === 0 && !confirm('Diesen Vermietvorgang wirklich ohne zugeordnetes Gerät speichern? Ein Vermietvorgang ohne Gerät ist in der Regel nicht sinnvoll.')) { $event.preventDefault(); }">
            @csrf

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

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <div class="flex items-center justify-between">
                            <label for="mieter_id" class="block text-sm font-medium text-gray-700">Mieter</label>
                            <button type="button" x-data="" x-on:click.prevent="$dispatch('open-modal', 'create-mieter')"
                                    class="text-xs text-orange-600 hover:underline">
                                + Neu anlegen
                            </button>
                        </div>
                        <select name="mieter_id" id="mieter_id" class="form-control w-full" required>
                            <option value="">— wählen —</option>
                            @foreach($mieter as $m)
                            <option value="{{ $m->id }}" @selected(old('mieter_id') == $m->id)>
                                {{ $m->bezeichnung }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="rent_start" class="block text-sm font-medium text-gray-700">Verleihbeginn</label>
                        <input type="text" name="rent_start" id="rent_start"
                               value="{{ old('rent_start') }}"
                               class="form-control datepicker w-full" placeholder="TT.MM.JJJJ" required>
                    </div>

                    <div>
                        <label for="rent_end" class="block text-sm font-medium text-gray-700">Verleihende</label>
                        <input type="text" name="rent_end" id="rent_end"
                               value="{{ old('rent_end') }}"
                               class="form-control datepicker w-full" placeholder="TT.MM.JJJJ" required>
                    </div>
                </div>

                <div class="mt-4">
                    <label for="bezeichnung" class="block text-sm font-medium text-gray-700">Bezeichnung</label>
                    <input type="text" name="bezeichnung" id="bezeichnung"
                           value="{{ old('bezeichnung') }}"
                           class="form-control w-full" placeholder="Wird beim Auswählen des Mieters automatisch vorgeschlagen">
                </div>
            </section>

            <section class="border-t pt-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Zugeordnete Geräte</h3>

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

                <p class="text-xs text-gray-500 mt-2">
                    Verfügbarkeit im gewählten Verleihzeitraum wird beim Speichern geprüft — nicht verfügbare Geräte werden übersprungen.
                </p>
            </section>

            <details class="border-t pt-6" @if($errors->hasAny(['transport_type_start', 'transport_type_end', 'notify_mieter'])) open @endif>
                <summary class="text-lg font-semibold text-gray-800 cursor-pointer select-none">Transport-Logistik</summary>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div>
                        <label for="transport_type_start" class="block text-sm font-medium text-gray-700">
                            Hinweg — wie kommt das Gerät zum Mieter?
                        </label>
                        <input type="text" name="transport_type_start" id="transport_type_start" class="form-control w-full" value="{{ old('transport_type_start') }}">
                    </div>

                    <div>
                        <label for="transport_type_end" class="block text-sm font-medium text-gray-700">
                            Rückweg — wie kommt es zurück?
                        </label>
                        <input type="text" name="transport_type_end" id="transport_type_end" class="form-control w-full" value="{{ old('transport_type_end') }}">
                    </div>
                </div>

                <label class="flex items-center gap-2 mt-4 text-sm text-gray-700">
                    <input type="checkbox" name="notify_mieter" value="1" @checked(old('notify_mieter'))>
                    Mieter automatisch benachrichtigen
                </label>
            </details>

            <details class="border-t pt-6" @if($errors->hasAny(['reminder_days_before_start', 'reminder_days_before_end', 'mailing_list_id'])) open @endif>
                <summary class="text-lg font-semibold text-gray-800 cursor-pointer select-none">Erinnerungen</summary>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div>
                        <label for="reminder_days_before_start" class="block text-sm font-medium text-gray-700">
                            Erinnerung vor Verleihbeginn (Tage)
                        </label>
                        <input type="number" min="0" max="60" name="reminder_days_before_start" id="reminder_days_before_start"
                               class="form-control w-full"
                               placeholder="Standard: {{ \App\Models\Setting::get('reminder_days_before_start', config('reminders.default_days_before')) }}"
                               value="{{ old('reminder_days_before_start') }}">
                    </div>
                    <div>
                        <label for="reminder_days_before_end" class="block text-sm font-medium text-gray-700">
                            Erinnerung vor Verleihende (Tage)
                        </label>
                        <input type="number" min="0" max="60" name="reminder_days_before_end" id="reminder_days_before_end"
                               class="form-control w-full"
                               placeholder="Standard: {{ \App\Models\Setting::get('reminder_days_before_end', config('reminders.default_days_before')) }}"
                               value="{{ old('reminder_days_before_end') }}">
                    </div>
                </div>

                <div class="mt-4">
                    <label for="mailing_list_id" class="block text-sm font-medium text-gray-700">Mailingliste</label>
                    <select name="mailing_list_id" id="mailing_list_id" class="form-control w-full">
                        <option value="">
                            {{ $defaultMailingList ? '— keine (Standardliste: '.$defaultMailingList->name.') —' : '— keine (keine Standardliste festgelegt) —' }}
                        </option>
                        @foreach($mailingLists as $list)
                        <option value="{{ $list->id }}" @selected(old('mailing_list_id') == $list->id)>
                            {{ $list->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
            </details>

            <div class="border-t pt-6 flex flex-col sm:flex-row gap-3 sm:justify-end">
                <a href="{{ route('vermietvorgaenge.index') }}"
                   class="inline-flex justify-center bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-4 rounded">
                    Abbrechen
                </a>

                <button type="submit"
                        class="inline-flex justify-center bg-orange-400 hover:bg-orange-500 text-white font-semibold py-2 px-4 rounded">
                    Speichern
                </button>
            </div>
        </form>
    </div>

    <x-modal name="create-mieter">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900">Neuen Mieter anlegen</h2>

            <div id="create-mieter-errors" class="mt-4 hidden bg-red-50 border border-red-300 text-red-700 px-4 py-3 rounded text-sm"></div>

            <div class="mt-4 space-y-4">
                <div>
                    <label for="new_mieter_bezeichnung" class="block text-sm font-medium text-gray-700">Bezeichnung</label>
                    <input type="text" id="new_mieter_bezeichnung" class="form-control w-full">
                </div>
                <div>
                    <label for="new_mieter_kontakt" class="block text-sm font-medium text-gray-700">Kontakt</label>
                    <input type="text" id="new_mieter_kontakt" class="form-control w-full">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="new_mieter_phone" class="block text-sm font-medium text-gray-700">Telefon</label>
                        <input type="text" id="new_mieter_phone" class="form-control w-full">
                    </div>
                    <div>
                        <label for="new_mieter_email" class="block text-sm font-medium text-gray-700">E-Mail</label>
                        <input type="email" id="new_mieter_email" class="form-control w-full">
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" x-on:click="$dispatch('close')"
                        class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-4 rounded">
                    Abbrechen
                </button>
                <button type="button" onclick="submitNewMieter()"
                        class="bg-orange-400 hover:bg-orange-500 text-white font-semibold py-2 px-4 rounded">
                    Anlegen
                </button>
            </div>
        </div>
    </x-modal>

    <script>
        function submitNewMieter() {
            const errorBox = document.getElementById('create-mieter-errors');
            errorBox.classList.add('hidden');
            errorBox.innerHTML = '';

            fetch('{{ route('mieter.store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    bezeichnung: document.getElementById('new_mieter_bezeichnung').value,
                    kontakt: document.getElementById('new_mieter_kontakt').value,
                    phone: document.getElementById('new_mieter_phone').value,
                    email: document.getElementById('new_mieter_email').value,
                }),
            })
            .then(async response => {
                const data = await response.json();

                if (!response.ok) {
                    const messages = Object.values(data.errors || { error: [data.message || 'Fehler beim Anlegen.'] }).flat();
                    errorBox.innerHTML = messages.join('<br>');
                    errorBox.classList.remove('hidden');
                    return;
                }

                const select = document.getElementById('mieter_id');
                const option = document.createElement('option');
                option.value = data.id;
                option.textContent = data.bezeichnung;
                select.appendChild(option);
                select.value = data.id;
                select.dispatchEvent(new Event('change', { bubbles: true }));

                document.getElementById('new_mieter_bezeichnung').value = '';
                document.getElementById('new_mieter_kontakt').value = '';
                document.getElementById('new_mieter_phone').value = '';
                document.getElementById('new_mieter_email').value = '';

                window.dispatchEvent(new CustomEvent('close-modal', { detail: 'create-mieter' }));
            })
            .catch(() => {
                errorBox.innerHTML = 'Netzwerkfehler beim Anlegen.';
                errorBox.classList.remove('hidden');
            });
        }

        function vermietvorgangItemPicker({ items }) {
            return {
                items,
                query: '',
                open: false,
                selected: [],
                get filteredItems() {
                    const needle = this.query.trim().toLowerCase();
                    return this.items.filter(item => !this.isSelected(item) && (needle === '' || item.label.toLowerCase().includes(needle)));
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

        document.addEventListener('DOMContentLoaded', function () {
            const mieterSelect = document.getElementById('mieter_id');
            const bezeichnungInput = document.getElementById('bezeichnung');
            let lastSuggested = '';

            mieterSelect.addEventListener('change', function () {
                if (!this.value) return;
                if (bezeichnungInput.value !== '' && bezeichnungInput.value !== lastSuggested) return;

                fetch(`{{ route('vermietvorgaenge.suggestBezeichnung') }}?mieter_id=${this.value}`)
                    .then(response => response.json())
                    .then(data => {
                        bezeichnungInput.value = data.bezeichnung;
                        lastSuggested = data.bezeichnung;
                    });
            });
        });
    </script>
</x-app-layout>
