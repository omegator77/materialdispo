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

    $colLink = fn (string $col, string $dir) => route($listRoute ?? 'items.index', array_merge($baseParams, [
        'sort_by' => $col,
        'sort_direction' => $dir,
    ]));
    $colToggle = fn (string $col) => ($sortBy === $col && $sortDir === 'asc') ? 'desc' : 'asc';
    $colIndicator = fn (string $col) => $sortBy === $col ? ($sortDir === 'desc' ? '↓' : '↑') : '';
@endphp

<div class="page">

    {{-- Desktop --}}
    <div class="hidden md:block card overflow-hidden">
        <table class="data-table">
            <thead>
                <tr>
                    @if(Auth::user()->isUser())
                    <th class="w-8"><input type="checkbox" class="bulk-select-all form-check" title="Alle sichtbaren auswählen" aria-label="Alle sichtbaren auswählen"></th>
                    @endif
                    <th>
                        <a href="{{ $colLink('group', $nextGroupDir) }}"
                           class="hover:text-orange-500 {{ $sortBy === 'group' ? 'text-orange-500' : '' }}"
                           title="Sortierung wechseln: Gruppen-Reihenfolge → A–Z → Z–A">
                            Gruppe <span class="text-xs">{{ $groupIndicator }}</span>
                        </a>
                        @if($groupModeLabel)
                            <span class="block text-[11px] font-normal text-gray-400">{{ $groupModeLabel }}</span>
                        @endif
                    </th>
                    <th>
                        <a href="{{ $colLink('nummer', $colToggle('nummer')) }}"
                           class="hover:text-orange-500 {{ $sortBy === 'nummer' ? 'text-orange-500' : '' }}">
                            Nr. <span class="text-xs">{{ $colIndicator('nummer') }}</span>
                        </a>
                    </th>
                    <th>
                        <a href="{{ $colLink('bezeichnung', $colToggle('bezeichnung')) }}"
                           class="hover:text-orange-500 {{ $sortBy === 'bezeichnung' ? 'text-orange-500' : '' }}">
                            Bezeichnung <span class="text-xs">{{ $colIndicator('bezeichnung') }}</span>
                        </a>
                    </th>
                    <th>Beschreibung</th>
                    <th>Vermieter</th>
                    @if(($listRoute ?? '') === 'items.rented')
                    <th>Mietzeitraum</th>
                    @endif
                    <th class="text-right">Aktionen</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                <tr class="border-b hover:bg-gray-50" data-search="{{ $item->bezeichnung }} {{ $item->nummer }}" data-item-id="{{ $item->id }}">
                    @if(Auth::user()->isUser())
                    <td><input type="checkbox" class="bulk-select form-check" value="{{ $item->id }}" aria-label="Gerät auswählen"></td>
                    @endif
                    <td>{{ $item->unit->bezeichnung ?? '—' }}</td>
                    <td>{{ $item->nummer ?: '—' }}</td>
                    <td class="font-medium">
                        <a href="{{ route('items.show', $item->id) }}" class="text-gray-900 hover:text-orange-500">
                            {{ $item->bezeichnung }}
                        </a>
                    </td>
                    <td>{{ $item->description ?: '—' }}</td>
                    <td>{{ $item->supplier->bezeichnung ?? 'Eigentum' }}</td>
                    @if(($listRoute ?? '') === 'items.rented')
                    <td class="whitespace-nowrap">@include('items.tables._mietzeitraum')</td>
                    @endif
                    <td>@include('items.tables._actions')</td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="empty-state">Keine Geräte gefunden.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Mobile --}}
    @if(Auth::user()->isUser())
    <label class="md:hidden flex items-center gap-2 text-sm text-gray-600 mb-2 px-1">
        <input type="checkbox" class="bulk-select-all form-check"> Alle sichtbaren auswählen
    </label>
    @endif
    <div class="md:hidden space-y-3">
        @forelse($items as $item)
        <div class="relative card p-4" data-search="{{ $item->bezeichnung }} {{ $item->nummer }}" data-item-id="{{ $item->id }}">
            @if(Auth::user()->isUser())
            <input type="checkbox" class="bulk-select absolute top-3 right-3 form-check" value="{{ $item->id }}" aria-label="Gerät auswählen">
            @endif
            <a href="{{ route('items.show', $item->id) }}" class="text-lg font-semibold text-gray-900 hover:text-orange-500">
                {{ $item->bezeichnung }}
            </a>
            <p class="text-sm text-gray-500">{{ $item->unit->bezeichnung ?? 'Keine Gruppe' }}</p>
            <div class="mt-3 space-y-1 text-sm text-gray-700">
                <p><span class="font-medium">Nummer:</span> {{ $item->nummer ?: '—' }}</p>
                <p><span class="font-medium">Beschreibung:</span> {{ $item->description ?: '—' }}</p>
                <p><span class="font-medium">Vermieter:</span> {{ $item->supplier->bezeichnung ?? 'Eigentum' }}</p>
                @if(($listRoute ?? '') === 'items.rented')
                <p><span class="font-medium">Mietzeitraum:</span> @include('items.tables._mietzeitraum')</p>
                @endif
            </div>
            <div class="mt-4">@include('items.tables._actions')</div>
        </div>
        @empty
        <div class="card card-pad text-center text-gray-500">Keine Geräte gefunden.</div>
        @endforelse
    </div>

    <div data-search-empty class="hidden mt-4 card card-pad text-center text-gray-500">
        Keine Geräte gefunden.
    </div>

</div>
