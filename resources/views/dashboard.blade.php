<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Dashboard
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 space-y-6">

            {{-- Kennzahlen --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white rounded-lg shadow-sm p-5">
                    <div class="text-sm text-gray-500">Aktive Produktionen</div>
                    <div class="text-3xl font-bold text-gray-900">{{ $activeProductionsCount }}</div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-5">
                    <div class="text-sm text-gray-500">Geräte gesamt</div>
                    <div class="text-3xl font-bold text-gray-900">{{ $itemsCount }}</div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-5">
                    <div class="text-sm text-gray-500">Heute gebucht</div>
                    <div class="text-3xl font-bold text-gray-900">{{ $todayBookedItemsCount }}</div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-5">
                    <div class="text-sm text-gray-500">Vermieter</div>
                    <div class="text-3xl font-bold text-gray-900">{{ $suppliersCount }}</div>
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
                        <li>⬜ Archiv für alte Produktionen</li>
                        <li>⬜ Globale Suche</li>
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
</x-app-layout>