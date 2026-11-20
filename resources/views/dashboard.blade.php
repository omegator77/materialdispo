<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Dashboard">
            <x-slot name="actions">
                <a href="{{ route('anleitung') }}" class="btn btn-secondary btn-sm">📖 Anleitung</a>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="page">

            {{-- Kennzahlen --}}
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4 items-start">
                <div class="card p-5 h-36">
                    <div class="text-sm text-gray-500">Aktive Vorgänge</div>
                    <div class="text-3xl font-bold text-gray-900">{{ $activeVorgaengeCount }}</div>
                </div>

                <div class="card p-5 h-36">
                    <div class="text-sm text-gray-500">Geräte gesamt</div>
                    <div class="text-3xl font-bold text-gray-900">{{ $itemsCount }}</div>
                    <div class="text-xs text-gray-400 mt-1">davon {{ $rentedItemsCount }} gemietet</div>
                </div>

                <div class="card p-5 h-36">
                    <div class="text-sm text-gray-500">Heute gebucht</div>
                    <div class="text-3xl font-bold text-gray-900">{{ $todayBookedItemsCount }}</div>
                </div>

                @if(Auth::user()->isUser())
                <a href="{{ route('activity-log.index') }}" class="card p-5 hover:bg-gray-50 transition-colors h-36 flex flex-col">
                    <div class="text-sm text-gray-500 mb-2 shrink-0">Letzte Aktionen</div>
                    <div class="flex-1 overflow-y-auto">
                        @include('dashboard._last-activities')
                    </div>
                </a>
                @else
                <div class="card p-5 h-36 flex flex-col">
                    <div class="text-sm text-gray-500 mb-2 shrink-0">Letzte Aktionen</div>
                    <div class="flex-1 overflow-y-auto">
                        @include('dashboard._last-activities')
                    </div>
                </div>
                @endif

                <div class="rounded-xl bg-gray-800 shadow-sm p-5 font-mono text-right h-36 flex flex-col justify-center">
                    <div class="text-xs text-gray-400 tracking-widest">LOCAL</div>
                    <div id="local-date" class="text-sm text-green-400"></div>
                    <div id="local-time" class="text-2xl font-bold text-green-400"></div>

                    <div class="mt-3 text-xs text-gray-400 tracking-widest">UTC</div>
                    <div id="utc-time" class="text-sm text-green-400"></div>
                </div>
            </div>

            <x-flash contained />

            @include('dashboard._attention')

            {{-- Kurzanleitung --}}
            {{--
            <details class="card overflow-hidden">
                <summary class="cursor-pointer px-5 py-4 font-semibold text-gray-900 hover:bg-gray-50">
                    📖 Kurzanleitung: Vom Anlegen bis zum Abfahren
                </summary>

                <div class="px-5 pb-5 pt-1 text-sm text-gray-700 space-y-3">
                    <ol class="space-y-2 list-decimal list-inside">
                        <li>
                            <strong>Produktion anlegen</strong> —
                            @if(Auth::user()->isUser())
                            <a href="{{ route('productions.create') }}" class="link">Produktionen → Neu</a>,
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
                            <strong>Packen</strong> — auf der <a href="{{ route('itemprods') }}" class="link">Packliste</a> den Button "Packvorgang" öffnen und jedes Gerät beim Einladen abhaken. Nach dem Abschließen ist die Checkliste gesperrt (Status-Punkt hier im Dashboard wird grün); bei Bedarf über "Wieder öffnen" korrigierbar.
                        </li>
                    </ol>

                    <p class="text-gray-500">
                        PDF-Export ist an jeder Stelle verfügbar: Packliste, VB-Protokoll, Abgleich-Report und Packvorgang-Checkliste zum Ausdrucken.
                    </p>
                </div>
            </details>
            --}}

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- Laufende Vorgänge --}}
                <div class="card card-pad flex flex-col max-h-96">
                    <h3 class="card-title mb-4 shrink-0">
                        Laufende Vorgänge
                    </h3>

                    <div class="overflow-y-auto">
                        @forelse($runningEntries as $entry)
                            @include('dashboard._production-entry', ['entry' => $entry, 'mode' => 'running'])
                        @empty
                            <div class="text-sm text-gray-500">
                                Heute läuft nichts.
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Kommende Vorgänge --}}
                <div class="card card-pad flex flex-col max-h-96">
                    <h3 class="card-title mb-4 shrink-0">
                        Nächste Vorgänge
                    </h3>

                    <div class="overflow-y-auto">
                        @forelse($upcomingEntries as $entry)
                            @include('dashboard._production-entry', ['entry' => $entry, 'mode' => 'upcoming'])
                        @empty
                            <div class="text-sm text-gray-500">
                                Nichts Anstehendes gefunden.
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Offene Vorgänge --}}
                <div class="card card-pad flex flex-col max-h-96">
                    <h3 class="card-title mb-4 shrink-0">
                        Offene Vorgänge
                    </h3>

                    <div class="overflow-y-auto">
                        @forelse($openVorgaenge as $entry)
                            @include('dashboard._vorgang-status', ['entry' => $entry])
                        @empty
                            <div class="text-sm text-gray-500">
                                Alle Miet-/Vermietvorgänge sind vollständig abgehakt.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Zuletzt angelegte Produktionen --}}
            <div class="card card-pad">
                <h3 class="card-title mb-4">
                    Zuletzt angelegte Produktionen
                </h3>

                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Produktion</th>
                                <th>Start</th>
                                <th>Ende</th>
                                <th>Aktion</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($latestProductions as $production)
                                <tr>
                                    <td class="font-medium text-gray-900">
                                        {{ $production->bezeichnung }}
                                    </td>
                                    <td class="text-gray-600">
                                        {{ \Carbon\Carbon::parse($production->booking_start)->format('d.m.Y') }}
                                    </td>
                                    <td class="text-gray-600">
                                        {{ \Carbon\Carbon::parse($production->booking_end)->format('d.m.Y') }}
                                    </td>
                                    <td>
                                        <a href="{{ route('productions.show', $production->id) }}" class="link">
                                            Öffnen
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="empty-state">
                                        Noch keine Produktionen vorhanden.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
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