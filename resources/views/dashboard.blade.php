<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Materialdispo Dashboard
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    Du bist eingeloggt.
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">Known Bugs</h3>

                    <ul class="list-disc list-inside space-y-2">
                        <li>Registrierungsmail / E-Mail-Funktion noch nicht eingerichtet.</li>
                        <li>Kamera-Konfig wird in der Packliste noch nicht sauber angezeigt.</li>
                        <li>Adminer zeigt unter PHP 8.4 Warnungen an.</li>
                    </ul>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">Fahrplan</h3>

                    <ul class="list-disc list-inside space-y-2">
                        <li>Registrierung deaktivieren und User nur manuell anlegen.</li>
                        <li>Angelegte Kamera Konfigs änderbar machen.</li>
                        <li>Supplier-Maske erstellen.</li>
                        <li>Items mit Suppliern sauber verknüpfen.</li>
                        <li>Packliste verbessern(Gruppieren nach Gerätegruppen, usw.).</li>
                        <li>PDF-Export finalisieren.</li>
                        <li>Rollen/Rechte für Benutzer vorbereiten.</li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
EOF