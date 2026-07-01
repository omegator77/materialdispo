<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Packvorgang: {{ $production->bezeichnung }}
            </h2>

            <div class="flex gap-2">
                <a href="{{ route('packvorgang.pdf', $production->id) }}"
                    class="inline-flex justify-center bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded">
                    PDF-Checkliste
                </a>
                <a href="{{ route('itemprods', ['production_id' => $production->id]) }}"
                    class="inline-flex justify-center bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-4 rounded">
                    Zurück
                </a>
            </div>
        </div>
    </x-slot>

    @php
    $locked = (bool) $production->packvorgang_confirmed_at;
    $initialPacks = [];
    foreach ($entries as $entry) {
        $pack = $packs->get($entry['item']->id);
        $initialPacks[$entry['item']->id] = [
            'packed' => (bool) $pack,
            'by' => $pack?->packedByUser?->name,
            'at' => $pack?->packed_at?->format('d.m.Y H:i'),
        ];
    }
    @endphp

    <div class="max-w-4xl w-11/12 mx-auto mt-6 space-y-6"
         x-data='packChecklist({
            packs: @json($initialPacks),
            toggleUrlBase: "{{ url('productions/'.$production->id.'/packvorgang/toggle') }}",
            locked: @json($locked)
         })'>

        @if(session('success'))
        <div class="bg-green-50 border border-green-300 text-green-700 px-4 py-3 rounded">
            {{ session('success') }}
        </div>
        @endif

        @if($locked)
        <div class="bg-gray-100 border border-gray-300 text-gray-700 px-4 py-3 rounded">
            🔒 Packvorgang ist abgeschlossen — die Checkliste ist gesperrt. Zum Ändern erst unten "Wieder öffnen".
        </div>
        @endif

        {{-- Fortschritt --}}
        <div class="bg-white border border-gray-300 rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between mb-2">
                <h2 class="text-lg font-semibold text-gray-800">Fortschritt</h2>
                <span class="text-sm font-medium text-gray-600" x-text="packedCount + ' / ' + total + ' Geräte im Rüstwagen'"></span>
            </div>

            <div class="w-full bg-gray-200 rounded-full h-3">
                <div class="h-3 rounded-full transition-all"
                     :class="packedCount === total && total > 0 ? 'bg-green-500' : 'bg-orange-400'"
                     :style="'width: ' + (total > 0 ? Math.round((packedCount / total) * 100) : 0) + '%'">
                </div>
            </div>
        </div>

        {{-- Checkliste --}}
        {{-- Kamerazüge: Geräte einer Konfiguration bleiben zusammen (werden oft gemeinsam auf einen Rollwagen gepackt) --}}
        @if($cameraGroups->isNotEmpty())
        <div class="bg-white border border-gray-300 rounded-lg shadow-md p-6">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">
                Kamerazüge
            </h3>

            <div class="space-y-4">
                @foreach($cameraGroups as $configId => $configEntries)
                <div class="border border-gray-200 rounded-lg p-3 bg-gray-50">
                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">
                        Kamera {{ $configEntries->first()['cam_number'] ?? '?' }}
                    </div>

                    <div class="space-y-2">
                        @foreach($configEntries as $entry)
                        @include('packvorgang._entry-row', ['entry' => $entry, 'locked' => $locked])
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Weitere Geräte: einzeln zugeordnete Geräte, gruppiert nach Gerätegruppe --}}
        @forelse($groupedEinzelEntries as $unitName => $unitEntries)
        <div class="bg-white border border-gray-300 rounded-lg shadow-md p-6">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">
                {{ $unitName }}
            </h3>

            <div class="space-y-2">
                @foreach($unitEntries as $entry)
                @include('packvorgang._entry-row', ['entry' => $entry, 'locked' => $locked])
                @endforeach
            </div>
        </div>
        @empty
        @if($cameraGroups->isEmpty())
        <div class="bg-white border border-gray-300 rounded-lg shadow-md p-6">
            <p class="text-gray-500">Diese Produktion hat noch keine Geräte in der Packliste.</p>
        </div>
        @endif
        @endforelse

        {{-- Abschluss --}}
        @if(Auth::user()->isUser())
        <div class="bg-white border border-gray-300 rounded-lg shadow-md p-6">
            @if($production->packvorgang_confirmed_at)
            @php $incomplete = $production->packedItemIds()->count() < $entries->count(); @endphp
            <div class="bg-green-50 border border-green-300 text-green-800 px-4 py-3 rounded mb-4">
                Packvorgang abgeschlossen am {{ $production->packvorgang_confirmed_at->format('d.m.Y H:i') }}
                von {{ $production->packvorgangConfirmedBy?->name ?? '—' }}.
                @if($incomplete)
                <span class="text-yellow-500" title="Abgeschlossen trotz fehlender Geräte">⚠ trotz fehlender Geräte</span>
                @endif
            </div>

            <form method="POST" action="{{ route('packvorgang.reopen', $production->id) }}">
                @csrf
                <button type="submit" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-4 rounded">
                    Wieder öffnen
                </button>
            </form>
            @else
            <div x-data="{ confirmIncomplete: false }">
                <template x-if="packedCount < total">
                    <div class="bg-yellow-50 border border-yellow-300 text-yellow-900 px-4 py-3 rounded mb-4">
                        <p class="mb-2">
                            ⚠ Noch nicht alle Geräte sind als gepackt markiert
                            (<span x-text="total - packedCount"></span> fehlen).
                        </p>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" x-model="confirmIncomplete" class="rounded border-gray-300">
                            Trotz fehlender Geräte abschließen
                        </label>
                    </div>
                </template>

                <form method="POST" action="{{ route('packvorgang.complete', $production->id) }}">
                    @csrf
                    <button type="submit"
                            :disabled="packedCount < total && !confirmIncomplete"
                            :class="(packedCount < total && !confirmIncomplete) ? 'opacity-50 cursor-not-allowed' : 'hover:bg-orange-500'"
                            class="bg-orange-400 text-white font-semibold py-2 px-4 rounded">
                        Packvorgang abschließen
                    </button>
                </form>
            </div>
            @endif
        </div>
        @endif
    </div>

    <script>
        function packChecklist({ packs, toggleUrlBase, locked }) {
            return {
                packs,
                locked,
                get total() {
                    return Object.keys(this.packs).length;
                },
                get packedCount() {
                    return Object.values(this.packs).filter(p => p.packed).length;
                },
                async toggle(itemId, event) {
                    const previous = { ...this.packs[itemId] };

                    if (this.locked) {
                        this.packs[itemId] = previous;
                        if (event?.target) {
                            event.target.checked = previous.packed;
                        }
                        return;
                    }

                    try {
                        const response = await fetch(toggleUrlBase + '/' + itemId, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                        });

                        if (response.status === 423) {
                            this.packs[itemId] = previous;
                            if (event?.target) {
                                event.target.checked = previous.packed;
                            }
                            alert('Packvorgang ist abgeschlossen und gesperrt. Bitte erst "Wieder öffnen".');
                            return;
                        }

                        if (!response.ok) {
                            throw new Error('Request failed');
                        }

                        const data = await response.json();

                        this.packs[itemId] = {
                            packed: data.packed,
                            by: data.packedBy,
                            at: data.packedAt,
                        };
                    } catch (e) {
                        this.packs[itemId] = previous;
                        if (event?.target) {
                            event.target.checked = previous.packed;
                        }
                        alert('Status konnte nicht gespeichert werden. Bitte erneut versuchen.');
                    }
                },
            };
        }
    </script>
</x-app-layout>
