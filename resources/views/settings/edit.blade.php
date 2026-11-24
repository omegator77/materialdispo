<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Einstellungen"></x-page-header>
    </x-slot>

    <div class="page-narrow">

        @if(session('success'))
        <div class="mb-4 bg-green-50 border border-green-300 text-green-700 px-4 py-3 rounded">
            {{ session('success') }}
        </div>
        @endif

        <form action="{{ route('settings.update') }}" method="POST" class="card card-pad space-y-6">
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
                <h3 class="card-title mb-4">Slack</h3>

                <div>
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input class="form-check" type="checkbox" name="slack_reminder_enabled" value="1" @checked(old('slack_reminder_enabled', $settings['slack_reminder_enabled']))>
                        Slack-Nachrichten für Miet-/Vermietvorgänge senden
                    </label>

                    <label for="slack_reminder_channel" class="block text-sm font-medium text-gray-700 mt-3">
                        Ziel-Kanal für Miet-/Vermietvorgangs-Nachrichten
                    </label>
                    <input type="text" name="slack_reminder_channel" id="slack_reminder_channel"
                           value="{{ old('slack_reminder_channel', $settings['slack_reminder_channel']) }}"
                           class="form-control w-full" placeholder="z. B. #material-dispo">
                    <p class="text-xs text-gray-500 mt-1">
                        Leer lassen, um den in der Server-Konfiguration hinterlegten Standardkanal zu verwenden.
                    </p>
                </div>

                <div class="mt-6 pt-4 border-t border-gray-100">
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input class="form-check" type="checkbox" name="slack_production_enabled" value="1" @checked(old('slack_production_enabled', $settings['slack_production_enabled']))>
                        Slack-Nachrichten für Produktionen senden
                    </label>

                    <label for="slack_production_channel" class="block text-sm font-medium text-gray-700 mt-3">
                        Ziel-Kanal für Produktions-Nachrichten
                    </label>
                    <input type="text" name="slack_production_channel" id="slack_production_channel"
                           value="{{ old('slack_production_channel', $settings['slack_production_channel']) }}"
                           class="form-control w-full" placeholder="z. B. #produktionen">
                    <p class="text-xs text-gray-500 mt-1">
                        Leer lassen, um den in der Server-Konfiguration hinterlegten Standardkanal zu verwenden.
                    </p>
                </div>

                <div class="mt-6 pt-4 border-t border-gray-100">
                    <label for="slack_order_channel" class="block text-sm font-medium text-gray-700">
                        Ziel-Kanal für Bestellungs-Nachrichten
                    </label>
                    <input type="text" name="slack_order_channel" id="slack_order_channel"
                           value="{{ old('slack_order_channel', $settings['slack_order_channel']) }}"
                           class="form-control w-full" placeholder="z. B. #bestellungen">
                    <p class="text-xs text-gray-500 mt-1">
                        Leer lassen, um den in der Server-Konfiguration hinterlegten Standardkanal zu verwenden.
                    </p>
                </div>
            </section>

            <section class="border-t pt-6">
                <h3 class="card-title mb-4">Standard-Vorlaufzeit für Erinnerungen</h3>
                <p class="text-xs text-gray-500 mb-4">
                    Gilt für alle Miet-/Vermietvorgänge ohne eigene, individuell hinterlegte Vorlaufzeit.
                </p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="reminder_days_before_start" class="form-label">
                            Vor Beginn (Tage)
                        </label>
                        <input type="number" min="0" max="60" name="reminder_days_before_start" id="reminder_days_before_start"
                               class="form-control w-full"
                               placeholder="Server-Standard: {{ config('reminders.default_days_before') }}"
                               value="{{ old('reminder_days_before_start', $settings['reminder_days_before_start']) }}">
                    </div>
                    <div>
                        <label for="reminder_days_before_end" class="form-label">
                            Vor Ende (Tage)
                        </label>
                        <input type="number" min="0" max="60" name="reminder_days_before_end" id="reminder_days_before_end"
                               class="form-control w-full"
                               placeholder="Server-Standard: {{ config('reminders.default_days_before') }}"
                               value="{{ old('reminder_days_before_end', $settings['reminder_days_before_end']) }}">
                    </div>
                </div>
            </section>

            <div class="border-t pt-6 flex justify-end">
                <button type="submit"
                        class="btn btn-primary">
                    Speichern
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
