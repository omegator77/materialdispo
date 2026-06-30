<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                VB-Protokoll: {{ $production->bezeichnung }}
            </h2>

            <div class="flex gap-2">
                @if(Auth::user()->isUser())
                <a href="{{ route('vb-protokoll.edit', $production->id) }}"
                    class="inline-flex justify-center bg-orange-400 hover:bg-orange-500 text-white font-semibold py-2 px-4 rounded">
                    Bearbeiten
                </a>
                @endif
                <a href="{{ route('productions.show', $production->id) }}"
                    class="inline-flex justify-center bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-4 rounded">
                    Zurück
                </a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-4xl w-11/12 mx-auto mt-6 space-y-6">

        @if(session('success'))
        <div class="bg-green-50 border border-green-300 text-green-700 px-4 py-3 rounded">
            {{ session('success') }}
        </div>
        @endif

        {{-- Kopf --}}
        <div class="bg-white border border-gray-300 rounded-lg shadow-md p-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="block text-gray-500">Kunde</span>
                    <span class="font-medium text-gray-900">{{ $vbProtokoll->kunde ?: '—' }}</span>
                </div>
                <div>
                    <span class="block text-gray-500">Produktionsort</span>
                    <span class="font-medium text-gray-900">{{ $vbProtokoll->produktionsort ?: '—' }}</span>
                </div>
                <div>
                    <span class="block text-gray-500">Produktionszeit</span>
                    <span class="font-medium text-gray-900">
                        {{ \Carbon\Carbon::parse($production->booking_start)->format('d.m.Y') }}
                        – {{ \Carbon\Carbon::parse($production->booking_end)->format('d.m.Y') }}
                    </span>
                </div>
                <div>
                    <span class="block text-gray-500">Erfasst von</span>
                    <span class="font-medium text-gray-900">{{ $vbProtokoll->creator?->name ?? '—' }} ({{ $vbProtokoll->created_at->format('d.m.Y') }})</span>
                </div>
            </div>
        </div>

        {{-- Anforderungen / Soll-Ist-Abgleich --}}
        @if($vbProtokoll->anforderungen->count())
        <div class="bg-white border border-gray-300 rounded-lg shadow-md p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Anforderungen – Abgleich mit Packliste</h2>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-gray-500">
                            <th class="py-2">Kategorie</th>
                            <th class="py-2">Benötigt</th>
                            <th class="py-2">Gepackt</th>
                            <th class="py-2">Status</th>
                            <th class="py-2">Notiz</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($vbProtokoll->abgleich() as $row)
                        <tr class="border-b last:border-b-0">
                            <td class="py-2 font-medium text-gray-900">{{ $row['unit']->bezeichnung ?? '—' }}</td>
                            <td class="py-2">{{ $row['benoetigt'] }}</td>
                            <td class="py-2">{{ $row['gepackt'] }}</td>
                            <td class="py-2">
                                @if($row['erfuellt'])
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">✓ erfüllt</span>
                                @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700">⚠ fehlt {{ $row['benoetigt'] - $row['gepackt'] }}</span>
                                @endif
                            </td>
                            <td class="py-2 text-gray-500">{{ $row['notiz'] ?: '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- Crew --}}
        <div class="bg-white border border-gray-300 rounded-lg shadow-md p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Crew</h2>

            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm">
                @php
                $crewFields = [
                    'crew_ul' => 'ÜL', 'crew_bt_sng' => 'BT/SNG', 'crew_ti' => 'TI',
                    'crew_sng' => 'SNG', 'crew_bt_dl' => 'BT DL', 'crew_tt' => 'TT',
                    'crew_tl' => 'TL', 'crew_ba' => 'BA', 'crew_ta' => 'TA',
                    'crew_kabelhilfen' => 'Kabelhilfen', 'crew_kamera' => 'Kamera', 'crew_evs' => 'EVS',
                ];
                @endphp
                @foreach($crewFields as $field => $label)
                <div>
                    <span class="block text-xs text-gray-500">{{ $label }}</span>
                    <span class="font-medium text-gray-900">{{ $vbProtokoll->{$field} ?: '—' }}</span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Besonderheiten / Kabelwege --}}
        @if($vbProtokoll->besonderheiten || $vbProtokoll->kabelwege)
        <div class="bg-white border border-gray-300 rounded-lg shadow-md p-6 space-y-4 text-sm">
            @if($vbProtokoll->besonderheiten)
            <div>
                <span class="block text-gray-500 mb-1">Besonderheiten</span>
                <span class="whitespace-pre-line">{{ $vbProtokoll->besonderheiten }}</span>
            </div>
            @endif
            @if($vbProtokoll->kabelwege)
            <div>
                <span class="block text-gray-500 mb-1">Kabelwege, Länge, Überbauten, Besonderheiten</span>
                <span class="whitespace-pre-line">{{ $vbProtokoll->kabelwege }}</span>
            </div>
            @endif
        </div>
        @endif

        {{-- Kameras --}}
        @if($vbProtokoll->kameras->count())
        <div class="bg-white border border-gray-300 rounded-lg shadow-md p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Kameras mit Optik und Stativart</h2>

            <table class="w-full text-sm">
                <tbody>
                    @foreach($vbProtokoll->kameras as $kamera)
                    <tr class="border-b last:border-b-0">
                        <td class="py-2 w-12 text-gray-500">{{ $kamera->position ?? '—' }}</td>
                        <td class="py-2 font-medium text-gray-900">{{ $kamera->bezeichnung }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        {{-- Audio / Technik --}}
        @php
        $textBlocks = [
            'audio_mic' => 'Mic Anzahl und Art',
            'audio_inear' => 'In Ear Sender/Empfänger',
            'audio_kommplatz' => 'Kommplatz/Sprechstellen/4-Draht',
            'isdn_funk' => 'ISDN/SIP/Funk',
            'maz_evs_usb' => 'MAZ/EVS/USB',
            'monitore' => 'Monitore',
            'sonstiges' => 'Sonstiges',
            'zeitplan' => 'Zeitplan',
        ];
        $filledBlocks = collect($textBlocks)->filter(fn ($label, $field) => !empty($vbProtokoll->{$field}));
        @endphp

        @if($filledBlocks->count())
        <div class="bg-white border border-gray-300 rounded-lg shadow-md p-6 space-y-4 text-sm">
            @foreach($filledBlocks as $field => $label)
            <div>
                <span class="block text-gray-500 mb-1">{{ $label }}</span>
                <span class="whitespace-pre-line">{{ $vbProtokoll->{$field} }}</span>
            </div>
            @endforeach
        </div>
        @endif

        {{-- Fotos --}}
        @if($vbProtokoll->fotos->count())
        <div class="bg-white border border-gray-300 rounded-lg shadow-md p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Fotos / Lagepläne</h2>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                @foreach($vbProtokoll->fotos as $foto)
                <a href="{{ $foto->url() }}" target="_blank">
                    <img src="{{ $foto->url() }}" alt="{{ $foto->original_name }}" class="w-full h-28 object-cover rounded border border-gray-200 hover:opacity-90">
                </a>
                @endforeach
            </div>
        </div>
        @endif

        @if(Auth::user()->isUser())
        <div class="flex justify-end">
            <form method="POST" action="{{ route('vb-protokoll.destroy', $production->id) }}"
                  onsubmit="return confirm('VB-Protokoll wirklich löschen?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded">
                    VB-Protokoll löschen
                </button>
            </form>
        </div>
        @endif

    </div>
</x-app-layout>
