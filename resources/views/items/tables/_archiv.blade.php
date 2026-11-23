{{-- Archiv der Mietgeräte: alle Mietvorgänge abgeschlossen. Erwartet: $archived (Collection<Item>) --}}
<div class="page">
    <details data-rent-archive class="card rent-archive">
        <summary class="cursor-pointer select-none px-4 py-3 text-sm font-semibold text-gray-700">
            Archiv ({{ $archived->count() }}) <span class="font-normal text-gray-500">– Geräte, deren Mietvorgänge alle abgeschlossen sind</span>
        </summary>

        {{-- Desktop --}}
        <div class="hidden md:block table-wrap border-t border-gray-100">
            <table class="data-table">
                <thead>
                    <tr>
                        @if(Auth::user()->isUser())
                        <th class="w-8"></th>
                        @endif
                        <th>Gruppe</th>
                        <th>Nr.</th>
                        <th>Bezeichnung</th>
                        <th>Vermieter</th>
                        <th>Zuletzt</th>
                        <th class="text-right">Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($archived as $item)
                    @php($letzter = $item->displayMietvorgang())
                    <tr data-search="{{ $item->bezeichnung }} {{ $item->nummer }}" data-item-id="{{ $item->id }}">
                        @if(Auth::user()->isUser())
                        <td><input type="checkbox" class="bulk-select form-check" value="{{ $item->id }}" aria-label="Gerät auswählen"></td>
                        @endif
                        <td>{{ $item->unit->bezeichnung ?? '—' }}</td>
                        <td>{{ $item->nummer ?: '—' }}</td>
                        <td class="font-medium">
                            <a href="{{ route('items.show', $item->id) }}" class="text-gray-900 hover:text-orange-500">{{ $item->bezeichnung }}</a>
                        </td>
                        <td>{{ $item->supplier->bezeichnung ?? '—' }}</td>
                        <td class="whitespace-nowrap">
                            zuletzt {{ $letzter->rent_start->format('d.m.Y') }} – {{ $letzter->rent_end->format('d.m.Y') }}
                        </td>
                        <td class="text-right">
                            <div class="row-actions justify-end">
                                <a href="{{ route('items.show', $item->id) }}" class="btn btn-secondary btn-sm">Details</a>
                                @if(Auth::user()->isUser())
                                <a href="{{ route('mietvorgaenge.create', ['suppliers_id' => $item->suppliers_id, 'item_id' => [$item->id]]) }}" class="btn btn-primary btn-sm">Erneut mieten</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Mobil --}}
        <div class="md:hidden space-y-3 p-3 border-t border-gray-100">
            @foreach($archived as $item)
            @php($letzter = $item->displayMietvorgang())
            <div class="relative card p-4" data-search="{{ $item->bezeichnung }} {{ $item->nummer }}" data-item-id="{{ $item->id }}">
                @if(Auth::user()->isUser())
                <input type="checkbox" class="bulk-select absolute top-3 right-3 form-check" value="{{ $item->id }}" aria-label="Gerät auswählen">
                @endif
                <a href="{{ route('items.show', $item->id) }}" class="text-lg font-semibold text-gray-900 hover:text-orange-500">{{ $item->bezeichnung }}</a>
                <p class="text-sm text-gray-500">{{ $item->unit->bezeichnung ?? 'Keine Gruppe' }}</p>
                <div class="mt-3 space-y-1 text-sm text-gray-700">
                    <p><span class="font-medium">Nummer:</span> {{ $item->nummer ?: '—' }}</p>
                    <p><span class="font-medium">Vermieter:</span> {{ $item->supplier->bezeichnung ?? '—' }}</p>
                    <p><span class="font-medium">Zuletzt:</span> {{ $letzter->rent_start->format('d.m.Y') }} – {{ $letzter->rent_end->format('d.m.Y') }}</p>
                </div>
                <div class="mt-4 flex flex-wrap gap-2">
                    <a href="{{ route('items.show', $item->id) }}" class="btn btn-secondary btn-sm">Details</a>
                    @if(Auth::user()->isUser())
                    <a href="{{ route('mietvorgaenge.create', ['suppliers_id' => $item->suppliers_id, 'item_id' => [$item->id]]) }}" class="btn btn-primary btn-sm">Erneut mieten</a>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </details>
</div>
