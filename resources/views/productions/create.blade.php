<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Neue Produktion
        </h2>
    </x-slot>

    <div class="max-w-2xl mx-auto mt-6 px-4">
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6">
            <form action="/productions" method="POST">
                @csrf

                {{-- Vorlage auswählen --}}
                @if($preset)
                    <div class="mb-5 flex items-center gap-3 bg-blue-50 border border-blue-200 rounded-lg px-4 py-3 text-sm text-blue-800">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        Vorlage: <strong>{{ $preset->bezeichnung }}</strong>
                        <a href="{{ route('productions.create') }}" class="ml-auto text-blue-500 hover:text-blue-700 hover:underline text-xs">Entfernen</a>
                    </div>
                    <input type="hidden" name="from_production_id" value="{{ $preset->id }}">
                @else
                    <div class="mb-5" x-data="{
                        open: false,
                        query: '',
                        results: [
                            @foreach($productions as $p)
                            { id: {{ $p->id }}, bezeichnung: @js($p->bezeichnung), booking_start: @js($p->booking_start ? \Carbon\Carbon::parse($p->booking_start)->format('d.m.Y') : null) },
                            @endforeach
                        ],
                        search() {
                            fetch('{{ route('productions.searchTemplates') }}?q=' + encodeURIComponent(this.query))
                                .then(r => r.json())
                                .then(data => this.results = data);
                        },
                        select(p) {
                            window.location = '{{ route('productions.create') }}?from=' + p.id;
                        }
                    }">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Vorlage (optional)</label>
                        <div class="relative">
                            <input type="text" x-model="query"
                                   @input.debounce.300ms="search()"
                                   @focus="open = true"
                                   @click.outside="open = false"
                                   placeholder="Vorlage suchen (z. B. Produktionsname)..."
                                   class="form-control w-full text-sm" autocomplete="off">

                            <div x-show="open" x-cloak
                                 class="absolute z-10 mt-1 w-full max-h-64 overflow-y-auto bg-white border border-gray-200 rounded-lg shadow-lg">
                                <template x-for="p in results" :key="p.id">
                                    <button type="button" @click="select(p)"
                                            class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50 border-b last:border-b-0">
                                        <span x-text="p.bezeichnung"></span>
                                        <span x-show="p.booking_start" class="text-gray-400" x-text="' (' + p.booking_start + ')'"></span>
                                    </button>
                                </template>
                                <div x-show="results.length === 0" class="px-3 py-2 text-sm text-gray-400">
                                    Keine Treffer.
                                </div>
                            </div>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Geräte der gewählten Produktion werden nach dem Speichern zur Übernahme angeboten. Es werden zunächst die letzten 25 Produktionen angezeigt — tippe, um ältere zu finden.</p>
                    </div>
                @endif

                <div class="space-y-4">
                    <div>
                        <label for="bezeichnung" class="block text-sm font-medium text-gray-700 mb-1">Bezeichnung</label>
                        <input type="text" name="bezeichnung" id="bezeichnung"
                               class="form-control w-full"
                               placeholder="Produktionsname ..."
                               value="{{ old('bezeichnung', $preset ? $preset->bezeichnung . ' (Kopie)' : '') }}">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="booking_start" class="block text-sm font-medium text-gray-700 mb-1">Mietbeginn</label>
                            <input type="text" name="booking_start" id="booking_start"
                                   class="form-control w-full datepicker"
                                   placeholder="TT.MM.JJJJ"
                                   value="{{ old('booking_start') }}">
                        </div>
                        <div>
                            <label for="booking_end" class="block text-sm font-medium text-gray-700 mb-1">Mietende</label>
                            <input type="text" name="booking_end" id="booking_end"
                                   class="form-control w-full datepicker"
                                   placeholder="TT.MM.JJJJ"
                                   value="{{ old('booking_end') }}">
                        </div>
                    </div>

                    <div>
                        <label for="packlist_notes" class="block text-sm font-medium text-gray-700 mb-1">Packlisten-Notiz</label>
                        <textarea name="packlist_notes" id="packlist_notes" rows="4"
                                  class="form-control w-full"
                                  placeholder="Hinweise für die gesamte Packliste...">{{ old('packlist_notes', $preset?->packlist_notes ?? '') }}</textarea>
                    </div>

                    @if($errors->any())
                        <ul class="text-sm text-red-600 space-y-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    @endif

                    <div class="flex justify-end pt-2">
                        <button type="submit"
                                class="bg-orange-400 hover:bg-orange-500 text-white font-semibold py-2 px-6 rounded focus:outline-none focus:ring">
                            {{ $preset ? 'Speichern & Geräte übernehmen →' : 'Speichern' }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @include('productions._table')
</x-app-layout>
