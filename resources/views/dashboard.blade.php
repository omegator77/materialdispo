<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Dashboard
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 space-y-6">

            {{-- Kennzahlen --}}
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4 items-start">
                <div class="bg-white rounded-lg shadow-sm p-5 h-36">
                    <div class="text-sm text-gray-500">Aktive Produktionen</div>
                    <div class="text-3xl font-bold text-gray-900">{{ $activeProductionsCount }}</div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-5 h-36">
                    <div class="text-sm text-gray-500">Geräte gesamt</div>
                    <div class="text-3xl font-bold text-gray-900">{{ $itemsCount }}</div>
                    <div class="text-xs text-gray-400 mt-1">davon {{ $rentedItemsCount }} gemietet</div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-5 h-36">
                    <div class="text-sm text-gray-500">Heute gebucht</div>
                    <div class="text-3xl font-bold text-gray-900">{{ $todayBookedItemsCount }}</div>
                </div>

                @if(Auth::user()->isUser())
                <a href="{{ route('activity-log.index') }}" class="bg-white rounded-lg shadow-sm p-5 hover:bg-gray-50 transition-colors h-36 flex flex-col">
                    <div class="text-sm text-gray-500 mb-2 shrink-0">Letzte Aktionen</div>
                    <div class="flex-1 overflow-y-auto">
                        @include('dashboard._last-activities')
                    </div>
                </a>
                @else
                <div class="bg-white rounded-lg shadow-sm p-5 h-36 flex flex-col">
                    <div class="text-sm text-gray-500 mb-2 shrink-0">Letzte Aktionen</div>
                    <div class="flex-1 overflow-y-auto">
                        @include('dashboard._last-activities')
                    </div>
                </div>
                @endif

                <div class="bg-gray-800 rounded-lg shadow-sm p-5 font-mono text-right h-36 flex flex-col justify-center">
                    <div class="text-xs text-gray-400 tracking-widest">LOCAL</div>
                    <div id="local-date" class="text-sm text-green-400"></div>
                    <div id="local-time" class="text-2xl font-bold text-green-400"></div>

                    <div class="mt-3 text-xs text-gray-400 tracking-widest">UTC</div>
                    <div id="utc-time" class="text-sm text-green-400"></div>
                </div>
            </div>

            {{-- Kurzanleitung --}}
            <details class="bg-white rounded-lg shadow-sm overflow-hidden">
                <summary class="cursor-pointer px-5 py-4 font-semibold text-gray-900 hover:bg-gray-50">
                    📖 Kurzanleitung: Vom Anlegen bis zum Abfahren
                </summary>

                <div class="px-5 pb-5 pt-1 text-sm text-gray-700 space-y-3">
                    <ol class="space-y-2 list-decimal list-inside">
                        <li>
                            <strong>Produktion anlegen</strong> —
                            @if(Auth::user()->isUser())
                            <a href="{{ route('productions.create') }}" class="text-orange-600 hover:underline">Produktionen → Neu</a>,
                            @else
                            unter "Produktionen → Neu" (Admin/Benutzer),
                            @endif
                            optional als Kopie einer bestehenden Produktion.
                        </li>
                        <li>
                            <strong>VB-Protokoll erfassen</strong> — Kunde, Crew, benötigte Geräte/Kamerakonfigurationen. Hier geht es nur um die Anforderungen, noch nicht um die tatsächliche Zuordnung.
                        </li>
                        <li>
                            <strong>Materialzuordnung</strong> — konkrete Geräte und Kamerazüge der Produktion zuweisen. Ein Live-Abgleich zeigt direkt, was vom VB-Protokoll noch fehlt.
                        </li>
                        <li>
                            <strong>Packen</strong> — auf der <a href="{{ route('itemprods') }}" class="text-orange-600 hover:underline">Packliste</a> den Button "Packvorgang" öffnen und jedes Gerät beim Einladen abhaken. Nach dem Abschließen ist die Checkliste gesperrt (Status-Punkt hier im Dashboard wird grün); bei Bedarf über "Wieder öffnen" korrigierbar.
                        </li>
                    </ol>

                    <p class="text-gray-500">
                        PDF-Export ist an jeder Stelle verfügbar: Packliste, VB-Protokoll, Abgleich-Report und Packvorgang-Checkliste zum Ausdrucken.
                    </p>
                </div>
            </details>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- Laufende Produktionen --}}
                <div class="bg-white rounded-lg shadow-sm p-5">
                    <h3 class="font-semibold text-gray-900 mb-4">
                        Laufende Produktionen
                    </h3>

                    @forelse($runningEntries as $entry)
                        @include('dashboard._production-entry', ['entry' => $entry, 'mode' => 'running'])
                    @empty
                        <div class="text-sm text-gray-500">
                            Heute läuft nichts.
                        </div>
                    @endforelse
                </div>

                {{-- Kommende Produktionen --}}
                <div class="bg-white rounded-lg shadow-sm p-5">
                    <h3 class="font-semibold text-gray-900 mb-4">
                        Nächste Produktionen
                    </h3>

                    @forelse($upcomingEntries as $entry)
                        @include('dashboard._production-entry', ['entry' => $entry, 'mode' => 'upcoming'])
                    @empty
                        <div class="text-sm text-gray-500">
                            Nichts Anstehendes gefunden.
                        </div>
                    @endforelse
                </div>

                {{-- Nächste Termine --}}
                <div class="bg-white rounded-lg shadow-sm p-5">
                    <h3 class="font-semibold text-gray-900 mb-4">
                        Nächste Termine
                    </h3>

                    @forelse($upcomingTransportEvents as $event)
                        @php
                            $daysUntil = \Carbon\Carbon::today()->diffInDays($event['date'], false);
                            $whenLabel = match(true) {
                                $daysUntil <= 0 => 'heute',
                                $daysUntil === 1 => 'morgen',
                                default => "in {$daysUntil} Tagen",
                            };

                            if ($event['kind'] === 'mietvorgang') {
                                $mv = $event['mietvorgang'];
                                $typeLabel = $event['type'] === 'start' ? 'Mietbeginn' : 'Mietende';
                                $title = $mv->supplier->bezeichnung ?? 'Vermieter gelöscht';
                                $subtitle = $mv->items->pluck('bezeichnung')->implode(', ');
                                $confirmRoute = route('mietvorgaenge.confirmTransport', [$mv, $event['type']]);
                                $linkRoute = route('mietvorgaenge.show', $mv);
                            } else {
                                $vv = $event['vermietvorgang'];
                                $typeLabel = $event['type'] === 'start' ? 'Verleihbeginn' : 'Verleihende';
                                $title = $vv->mieter->bezeichnung ?? 'Mieter gelöscht';
                                $subtitle = $vv->items->pluck('bezeichnung')->implode(', ');
                                $confirmRoute = route('vermietvorgaenge.confirmTransport', [$vv, $event['type']]);
                                $linkRoute = route('vermietvorgaenge.show', $vv);
                            }
                        @endphp
                        <div class="flex items-start gap-3 border-b last:border-b-0 py-3">
                            <form action="{{ $confirmRoute }}" method="POST" class="mt-0.5 shrink-0">
                                @csrf
                                <button type="submit" title="Als geklärt markieren"
                                        class="w-5 h-5 rounded border border-gray-300 hover:border-orange-400 hover:bg-orange-50 block"></button>
                            </form>
                            <a href="{{ $linkRoute }}" class="flex-1 hover:text-orange-600">
                                <div class="font-medium text-gray-900">
                                    {{ ucfirst($whenLabel) }} {{ $typeLabel }} — {{ $title }}
                                </div>
                                <div class="text-sm text-gray-500">
                                    {{ $subtitle }}
                                </div>
                            </a>
                        </div>
                    @empty
                        <div class="text-sm text-gray-500">
                            Keine anstehenden Termine in den nächsten 14 Tagen.
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Zuletzt angelegte Produktionen --}}
            <div class="bg-white rounded-lg shadow-sm p-5">
                <h3 class="font-semibold text-gray-900 mb-4">
                    Zuletzt angelegte Produktionen
                </h3>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b text-left text-gray-500">
                                <th class="py-2">Produktion</th>
                                <th class="py-2">Start</th>
                                <th class="py-2">Ende</th>
                                <th class="py-2">Aktion</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($latestProductions as $production)
                                <tr class="border-b last:border-b-0">
                                    <td class="py-3 font-medium text-gray-900">
                                        {{ $production->bezeichnung }}
                                    </td>
                                    <td class="py-3 text-gray-600">
                                        {{ \Carbon\Carbon::parse($production->booking_start)->format('d.m.Y') }}
                                    </td>
                                    <td class="py-3 text-gray-600">
                                        {{ \Carbon\Carbon::parse($production->booking_end)->format('d.m.Y') }}
                                    </td>
                                    <td class="py-3">
                                        <a href="{{ route('productions.show', $production->id) }}" class="text-blue-600 hover:underline">
                                            Öffnen
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-4 text-gray-500">
                                        Noch keine Produktionen vorhanden.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Roadmap --}}
            <details class="bg-white rounded-lg shadow-sm overflow-hidden">
                <summary class="cursor-pointer px-5 py-4 font-semibold text-gray-900 hover:bg-gray-50">
                    Roadmap
                </summary>

                <ul class="space-y-2 text-sm text-gray-700 px-5 pb-5 pt-1">
                    <li>✅ Registrierung deaktiviert</li>
                    <li>✅ Mailversand eingerichtet</li>
                    <li>✅ Timeline Grundversion</li>
                    <li>✅ Benutzer-/Rollensystem (Admin/Benutzer/Betrachter)</li>
                    <li>✅ Aktivitätsprotokoll</li>
                    <li>✅ Archiv für alte Produktionen (Packliste)</li>
                    <li>✅ Echtzeit-Suche (Geräte, Vorlagen, Packen)</li>
                    <li>✅ VB-Protokoll mit Soll/Ist-Abgleich</li>
                    <li>✅ Gerätetypen & typbasierte Kamerakonfiguration im VB-Protokoll</li>
                    <li>✅ PDF-Export für VB-Protokoll & Abgleich-Report</li>
                    <li>✅ Packvorgang: Checkliste je Gerät, Kamerazüge gruppiert, Sperre nach Abschluss</li>
                    <li>✅ Transport-Erinnerungen für Mietgeräte (Mietvorgänge, Mailinglisten)</li>
                    <li>⬜ Globale Suche über alle Bereiche</li>
                    <li>⬜ Packlisten per Mail</li>
                    <li>⬜ QR-Code je Gerät zum Abhaken per Handykamera im Packvorgang</li>
                </ul>
            </details>

        </div>
    </div>

    <script>
        function updateDashboardClock() {
            const now = new Date();

            document.getElementById('local-date').innerText =
                now.toLocaleDateString('de-DE');

            document.getElementById('local-time').innerText =
                now.toLocaleTimeString('de-DE');

            document.getElementById('utc-time').innerText =
                now.toISOString().substring(11, 19);
        }

        setInterval(updateDashboardClock, 1000);
        updateDashboardClock();
    </script>
</x-app-layout>