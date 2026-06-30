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
                </div>

                <div class="bg-white rounded-lg shadow-sm p-5 h-36">
                    <div class="text-sm text-gray-500">Heute gebucht</div>
                    <div class="text-3xl font-bold text-gray-900">{{ $todayBookedItemsCount }}</div>
                </div>

                @if(Auth::user()->isAdmin())
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

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- Laufende Produktionen --}}
                <div class="bg-white rounded-lg shadow-sm p-5">
                    <h3 class="font-semibold text-gray-900 mb-4">
                        Laufende Produktionen
                    </h3>

                    @forelse($runningProductions as $production)
                        <a href="{{ route('productions.show', $production->id) }}" class="block border-b last:border-b-0 py-3 hover:bg-gray-50">
                            <div class="font-medium text-gray-900">
                                {{ $production->bezeichnung }}
                            </div>
                            <div class="text-sm text-gray-500">
                                {{ \Carbon\Carbon::parse($production->booking_start)->format('d.m.Y') }}
                                –
                                {{ \Carbon\Carbon::parse($production->booking_end)->format('d.m.Y') }}
                            </div>
                        </a>
                    @empty
                        <div class="text-sm text-gray-500">
                            Heute läuft keine Produktion.
                        </div>
                    @endforelse
                </div>

                {{-- Kommende Produktionen --}}
                <div class="bg-white rounded-lg shadow-sm p-5">
                    <h3 class="font-semibold text-gray-900 mb-4">
                        Nächste Produktionen
                    </h3>

                    @forelse($upcomingProductions as $production)
                        <a href="{{ route('productions.show', $production->id) }}" class="block border-b last:border-b-0 py-3 hover:bg-gray-50">
                            <div class="font-medium text-gray-900">
                                {{ $production->bezeichnung }}
                            </div>
                            <div class="text-sm text-gray-500">
                                Start:
                                {{ \Carbon\Carbon::parse($production->booking_start)->format('d.m.Y') }}
                            </div>
                        </a>
                    @empty
                        <div class="text-sm text-gray-500">
                            Keine kommenden Produktionen gefunden.
                        </div>
                    @endforelse
                </div>

                {{-- Roadmap klein --}}
                <div class="bg-white rounded-lg shadow-sm p-5">
                    <h3 class="font-semibold text-gray-900 mb-4">
                        Roadmap
                    </h3>

                    <ul class="space-y-2 text-sm text-gray-700">
                        <li>✅ Registrierung deaktiviert</li>
                        <li>✅ Mailversand eingerichtet</li>
                        <li>✅ Timeline Grundversion</li>
                        <li>✅ Benutzer-/Rollensystem (Admin/Benutzer/Betrachter)</li>
                        <li>✅ Aktivitätsprotokoll</li>
                        <li>✅ Archiv für alte Produktionen (Packliste)</li>
                        <li>✅ Echtzeit-Suche (Geräte, Vorlagen, Packen)</li>
                        <li>✅ VB-Protokoll mit Soll/Ist-Abgleich</li>
                        <li>✅ Gerätetypen & typbasierte Kamerakonfiguration im VB-Protokoll</li>
                        <li>⬜ Globale Suche über alle Bereiche</li>
                        <li>⬜ Packlisten per Mail</li>
                    </ul>
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