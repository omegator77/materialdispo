@include('items.tables._filter')

@php
    // Aktive Sortierung bestimmen. Ohne Auswahl: nach Gruppen-Reihenfolge
    // gruppiert (group/custom) – identisch zur Controller-Vorgabe.
    $sortBy = in_array(request('sort_by'), ['group', 'nummer', 'bezeichnung'], true)
        ? request('sort_by')
        : 'group';

    if ($sortBy === 'group') {
        $sortDir = in_array(request('sort_direction'), ['custom', 'asc', 'desc'], true)
            ? request('sort_direction')
            : 'custom';
    } else {
        $sortDir = request('sort_direction') === 'desc' ? 'desc' : 'asc';
    }

    $baseParams = request()->all();

    // Gruppe zyklisch: wie in Gruppenansicht → A–Z → Z–A → …
    $nextGroupDir = $sortBy === 'group'
        ? ['custom' => 'asc', 'asc' => 'desc', 'desc' => 'custom'][$sortDir]
        : 'custom';
    $groupIndicator = $sortBy !== 'group'
        ? '↕'
        : ['custom' => '↕', 'asc' => '↑', 'desc' => '↓'][$sortDir];
    $groupModeLabel = $sortBy === 'group'
        ? ['custom' => 'Gruppen-Reihenfolge', 'asc' => 'A–Z', 'desc' => 'Z–A'][$sortDir]
        : null;

    $colLink = fn (string $col, string $dir) => route('items.index', array_merge($baseParams, [
        'sort_by' => $col,
        'sort_direction' => $dir,
    ]));
    $colToggle = fn (string $col) => ($sortBy === $col && $sortDir === 'asc') ? 'desc' : 'asc';
    $colIndicator = fn (string $col) => $sortBy === $col ? ($sortDir === 'desc' ? '↓' : '↑') : '';
@endphp

<div class="max-w-7xl w-11/12 mx-auto mt-6">

    {{-- Desktop --}}
    <div class="hidden md:block bg-white border border-gray-300 rounded-lg shadow-md overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-100 border-b">
                <tr>
                    <th class="text-left px-4 py-3">
                        <a href="{{ $colLink('group', $nextGroupDir) }}"
                           class="hover:text-orange-500 {{ $sortBy === 'group' ? 'text-orange-500' : '' }}"
                           title="Sortierung wechseln: Gruppen-Reihenfolge → A–Z → Z–A">
                            Gruppe <span class="text-xs">{{ $groupIndicator }}</span>
                        </a>
                        @if($groupModeLabel)
                            <span class="block text-[11px] font-normal text-gray-400">{{ $groupModeLabel }}</span>
                        @endif
                    </th>
                    <th class="text-left px-4 py-3">
                        <a href="{{ $colLink('nummer', $colToggle('nummer')) }}"
                           class="hover:text-orange-500 {{ $sortBy === 'nummer' ? 'text-orange-500' : '' }}">
                            Nr. <span class="text-xs">{{ $colIndicator('nummer') }}</span>
                        </a>
                    </th>
                    <th class="text-left px-4 py-3">
                        <a href="{{ $colLink('bezeichnung', $colToggle('bezeichnung')) }}"
                           class="hover:text-orange-500 {{ $sortBy === 'bezeichnung' ? 'text-orange-500' : '' }}">
                            Bezeichnung <span class="text-xs">{{ $colIndicator('bezeichnung') }}</span>
                        </a>
                    </th>
                    <th class="text-left px-4 py-3">Beschreibung</th>
                    <th class="text-left px-4 py-3">Vermieter</th>
                    <th class="text-right px-4 py-3">Aktionen</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                <tr class="border-b hover:bg-gray-50" data-search="{{ $item->bezeichnung }} {{ $item->nummer }}">
                    <td class="px-4 py-3">{{ $item->unit->bezeichnung ?? '—' }}</td>
                    <td class="px-4 py-3">{{ $item->nummer ?: '—' }}</td>
                    <td class="px-4 py-3 font-medium">
                        <a href="{{ route('items.show', $item->id) }}" class="text-gray-900 hover:text-orange-500">
                            {{ $item->bezeichnung }}
                        </a>
                    </td>
                    <td class="px-4 py-3 text-gray-700">{{ $item->description ?: '—' }}</td>
                    <td class="px-4 py-3">{{ $item->supplier->bezeichnung ?? 'Eigentum' }}</td>
                    <td class="px-4 py-3">@include('items.tables._actions')</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-6 text-gray-500">Keine Geräte gefunden.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Mobile --}}
    <div class="md:hidden space-y-3">
        @forelse($items as $item)
        <div class="bg-white border border-gray-300 rounded-lg shadow-md p-4" data-search="{{ $item->bezeichnung }} {{ $item->nummer }}">
            <a href="{{ route('items.show', $item->id) }}" class="text-lg font-semibold text-gray-900 hover:text-orange-500">
                {{ $item->bezeichnung }}
            </a>
            <p class="text-sm text-gray-500">{{ $item->unit->bezeichnung ?? 'Keine Gruppe' }}</p>
            <div class="mt-3 space-y-1 text-sm text-gray-700">
                <p><span class="font-medium">Nummer:</span> {{ $item->nummer ?: '—' }}</p>
                <p><span class="font-medium">Beschreibung:</span> {{ $item->description ?: '—' }}</p>
                <p><span class="font-medium">Vermieter:</span> {{ $item->supplier->bezeichnung ?? 'Eigentum' }}</p>
                @if($item->mietvorgaenge->isNotEmpty())
                <p>
                    <span class="font-medium">Mietzeitraum:</span>
                    {{ $item->mietvorgaenge->first()->rent_start->format('d.m.Y') }}
                    – {{ $item->mietvorgaenge->first()->rent_end->format('d.m.Y') }}
                    @if($item->mietvorgaenge->count() > 1)
                        <details class="inline">
                            <summary class="inline cursor-pointer text-orange-600 hover:underline text-xs">+{{ $item->mietvorgaenge->count() - 1 }} weitere</summary>
                            <span class="block mt-0.5">
                                @foreach($item->mietvorgaenge->slice(1) as $mv)
                                    {{ $mv->rent_start->format('d.m.Y') }} – {{ $mv->rent_end->format('d.m.Y') }}@if(!$loop->last), @endif
                                @endforeach
                            </span>
                        </details>
                    @endif
                </p>
                @endif
            </div>
            <div class="mt-4">@include('items.tables._actions')</div>
        </div>
        @empty
        <div class="bg-white border border-gray-300 rounded-lg p-6 text-center text-gray-500">Keine Geräte gefunden.</div>
        @endforelse
    </div>

    <div data-search-empty class="hidden mt-4 bg-white border border-gray-300 rounded-lg p-6 text-center text-gray-500">
        Keine Geräte gefunden.
    </div>

</div>
