<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Neuen Mietvorgang anlegen
        </h2>
    </x-slot>

    <div class="max-w-3xl w-11/12 mx-auto mt-6">
        <form action="{{ route('mietvorgaenge.store') }}" method="POST" class="bg-white p-6 border border-gray-300 rounded-lg shadow-md space-y-6">
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
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Vermieter &amp; Zeitraum</h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="suppliers_id" class="block text-sm font-medium text-gray-700">Vermieter</label>
                        <select name="suppliers_id" id="suppliers_id" class="form-control w-full" required>
                            <option value="">— wählen —</option>
                            @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected(old('suppliers_id') == $supplier->id)>
                                {{ $supplier->bezeichnung }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="rent_start" class="block text-sm font-medium text-gray-700">Mietbeginn</label>
                        <input type="text" name="rent_start" id="rent_start"
                               value="{{ old('rent_start') }}"
                               class="form-control datepicker w-full" placeholder="TT.MM.JJJJ" required>
                    </div>

                    <div>
                        <label for="rent_end" class="block text-sm font-medium text-gray-700">Mietende</label>
                        <input type="text" name="rent_end" id="rent_end"
                               value="{{ old('rent_end') }}"
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
                        <select name="transport_type_start" id="transport_type_start" class="form-control w-full">
                            <option value="">— wählen —</option>
                            @foreach(\App\Models\Mietvorgang::TRANSPORT_TYPES_START as $value => $label)
                            <option value="{{ $value }}" @selected(old('transport_type_start') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="transport_type_end" class="block text-sm font-medium text-gray-700">
                            Rückweg — wie geht es zurück?
                        </label>
                        <select name="transport_type_end" id="transport_type_end" class="form-control w-full">
                            <option value="">— wählen —</option>
                            @foreach(\App\Models\Mietvorgang::TRANSPORT_TYPES_END as $value => $label)
                            <option value="{{ $value }}" @selected(old('transport_type_end') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <label class="flex items-center gap-2 mt-4 text-sm text-gray-700">
                    <input type="checkbox" name="notify_supplier" value="1" @checked(old('notify_supplier'))>
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
                               value="{{ old('reminder_days_before_start') }}">
                    </div>
                    <div>
                        <label for="reminder_days_before_end" class="block text-sm font-medium text-gray-700">
                            Erinnerung vor Mietende (Tage)
                        </label>
                        <input type="number" min="0" max="60" name="reminder_days_before_end" id="reminder_days_before_end"
                               class="form-control w-full"
                               placeholder="Standard: {{ config('reminders.default_days_before') }}"
                               value="{{ old('reminder_days_before_end') }}">
                    </div>
                </div>

                <div class="mt-4">
                    <label for="mailing_list_id" class="block text-sm font-medium text-gray-700">Mailingliste</label>
                    <select name="mailing_list_id" id="mailing_list_id" class="form-control w-full">
                        <option value="">— keine (nur Standard-Mailingliste, falls konfiguriert) —</option>
                        @foreach($mailingLists as $list)
                        <option value="{{ $list->id }}" @selected(old('mailing_list_id') == $list->id)>
                            {{ $list->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
            </section>

            <div class="border-t pt-6 flex flex-col sm:flex-row gap-3 sm:justify-end">
                <a href="{{ route('mietvorgaenge.index') }}"
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
</x-app-layout>
