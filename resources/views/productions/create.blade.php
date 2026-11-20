<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Neue Produktion"></x-page-header>
    </x-slot>

    <div class="page-narrow">
        <div class="card card-pad">
            <form action="/productions" method="POST">
                @csrf

                {{-- Vorlage auswählen --}}
                @if($preset)
                    <div class="mb-5 flex items-center gap-3 bg-blue-50 border border-blue-200 rounded-lg px-4 py-3 text-sm text-blue-800">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        Vorlage: <strong>{{ $preset->bezeichnung }}</strong>
                        <a href="{{ route('productions.create') }}" class="link ml-auto text-xs">Entfernen</a>
                    </div>
                    <input type="hidden" name="from_production_id" value="{{ $preset->id }}">
                @else
                    <div class="mb-5" x-data="{
                        query: '',
                        older: [],
                        visible() {
                            const q = this.query.trim().toLowerCase();
                            return [...this.$refs.list.querySelectorAll('[data-name]')]
                                .filter(el => q === '' || el.dataset.name.includes(q)).length;
                        },
                        match(name) { const q = this.query.trim().toLowerCase(); return q === '' || name.includes(q); },
                        shown: @js($productions->pluck('id')),
                        search() {
                            const q = this.query;
                            if (q.trim().length < 2) { this.older = []; return; }
                            fetch('{{ route('productions.searchTemplates') }}?q=' + encodeURIComponent(q) + '&exclude=' + this.shown.join(','), { headers: { Accept: 'application/json' } })
                                .then(r => r.ok ? r.json() : [])
                                .then(data => { if (q === this.query) this.older = data; })
                                .catch(() => { this.older = []; });
                        }
                    }">
                        <label for="template-search" class="form-label">Aus früherer Produktion starten</label>
                        <input type="text" id="template-search" x-model="query" @input.debounce.300ms="search()"
                               placeholder="Produktion suchen ..." class="form-control w-full text-sm mb-2" autocomplete="off">

                        <div x-ref="list" data-template-list class="max-h-48 overflow-y-auto rounded-lg border border-gray-200 divide-y divide-gray-100">
                            @foreach($productions as $p)
                                <a href="{{ route('productions.create', ['from' => $p->id]) }}"
                                   data-name="{{ mb_strtolower($p->bezeichnung) }}"
                                   x-show="match($el.dataset.name)"
                                   class="flex justify-between gap-3 px-3 py-2 text-sm hover:bg-gray-50">
                                    <span class="font-medium text-gray-900">{{ $p->bezeichnung }}</span>
                                    <span class="text-gray-400 whitespace-nowrap">{{ $p->booking_start ? \Carbon\Carbon::parse($p->booking_start)->format('d.m.Y') : '' }}</span>
                                </a>
                            @endforeach
                            <template x-for="p in older" :key="p.id">
                                <a :href="'{{ route('productions.create') }}?from=' + p.id"
                                   class="flex justify-between gap-3 px-3 py-2 text-sm hover:bg-gray-50">
                                    <span class="font-medium text-gray-900" x-text="p.bezeichnung"></span>
                                    <span class="text-gray-400 whitespace-nowrap" x-text="p.booking_start"></span>
                                </a>
                            </template>
                            <p x-show="query.trim() !== '' && visible() === 0 && older.length === 0" x-cloak class="px-3 py-2 text-sm text-gray-400">Keine Treffer.</p>
                            <span hidden data-template-list-end></span>
                        </div>
                        <p class="form-hint mt-1">Nach dem Speichern wählst du, welche Geräte übernommen werden. Ohne Auswahl startest du leer.</p>
                    </div>
                @endif

                <div class="space-y-4">
                    <div>
                        <label for="bezeichnung" class="form-label">Bezeichnung</label>
                        <input type="text" name="bezeichnung" id="bezeichnung"
                               class="form-control w-full"
                               placeholder="Produktionsname ..."
                               value="{{ old('bezeichnung', $preset ? $preset->bezeichnung . ' (Kopie)' : '') }}">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="booking_start" class="form-label">Produktionsbeginn</label>
                            <input type="text" name="booking_start" id="booking_start"
                                   class="form-control w-full datepicker"
                                   placeholder="TT.MM.JJJJ"
                                   value="{{ old('booking_start') }}">
                        </div>
                        <div>
                            <label for="booking_end" class="form-label">Produktionsende <span class="font-normal text-gray-400">(leer = eintägig)</span></label>
                            <input type="text" name="booking_end" id="booking_end"
                                   class="form-control w-full datepicker"
                                   placeholder="TT.MM.JJJJ"
                                   value="{{ old('booking_end') }}">
                        </div>
                    </div>

                    <div>
                        <label for="packlist_notes" class="form-label">Packlisten-Notiz</label>
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
                                class="btn btn-primary focus:outline-none focus:ring">
                            {{ $preset ? 'Speichern & Geräte übernehmen →' : 'Speichern' }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @include('productions._table')
</x-app-layout>
