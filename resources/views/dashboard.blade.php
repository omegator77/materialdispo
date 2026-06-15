<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Materialdispo   Dashboard
        </h2>
    </x-slot>

    <div class="max-w-7xl w-11/12 mx-auto mt-6 space-y-6">

    <div class="bg-white border border-gray-300 rounded-lg shadow-md p-6">
        <h2 class="text-2xl font-bold text-gray-900">
            MaterialDispo
        </h2>

        <p class="text-gray-600 mt-2">
            Version 0.9 Alpha
        </p>

        <p class="mt-4 text-gray-700">
            Die grundlegende Materialverwaltung, Vermieterlogik, Produktionen,
            Packlisten, Kamera-Konfigurationen und der PDF-Export sind funktionsfähig.
        </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <div class="bg-white border border-gray-300 rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">
                Aktueller Stand
            </h3>

            <ul class="space-y-2 text-gray-700">
                <li>✅ Eigenmaterial und Mietmaterial werden über den Vermieter unterschieden.</li>
                <li>✅ Mietmaterial benötigt einen gültigen Mietzeitraum.</li>
                <li>✅ Produktionen prüfen Materialverfügbarkeit.</li>
                <li>✅ Kamera-Konfigurationen werden in Packlisten und PDFs berücksichtigt.</li>
                <li>✅ Wichtige Ansichten sind desktop- und mobilfreundlich überarbeitet.</li>
            </ul>
        </div>

        <div class="bg-white border border-gray-300 rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">
                Nächste Schritte
            </h3>

            <ul class="space-y-2 text-gray-700">
                <li>▢ Kalender-/Timeline-Ansicht für Produktionen und Geräte</li>
                <li>▢ Archiv für abgeschlossene Produktionen</li>
                <li>▢ Packlisten-Presets für wiederkehrende Produktionen</li>
                <li>▢ Suche, Filter und Sortierung weiter verbessern</li>
                <li>▢ Erweiterung
                    <ul class="list-disc list-inside mt-2 text-gray-600">
                        <li>▢ Benutzerverwaltung und Rollen</li>
                        <li>▢ Benachrichtigungen und Erinnerungen</li>
                        <li>▢ Erweiterte Berichte und Statistiken</li>
                        <li>▢ Mehr Eigenschafte pro Gerät speicherbar</li>
                    </ul>
                    </ul>
                </li>
            </ul>
        </div>

    </div>

</div>
</x-app-layout>
