@include('items.tables._filter')

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
                        <a href="{{ route('items.index', array_merge(request()->all(), ['sort_by' => 'nummer', 'sort_direction' => request('sort_direction') == 'asc' ? 'desc' : 'asc'])) }}" class="hover:text-orange-500">Nr.</a>
                    </th>
                    <th>
                        <a href="{{ route('items.index', array_merge(request()->all(), ['sort_by' => 'bezeichnung', 'sort_direction' => request('sort_direction') == 'asc' ? 'desc' : 'asc'])) }}" class="hover:text-orange-500">Bezeichnung</a>
                    </th>
                    <th>Hersteller</th>
                    <th>Modell</th>
                    <th>Zoom</th>
                    <th>Seriennummer</th>
                    <th>Vermieter</th>
                    <th class="text-right">Aktionen</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                <tr class="border-b hover:bg-gray-50" data-search="{{ $item->bezeichnung }} {{ $item->nummer }}" data-item-id="{{ $item->id }}">
                    @if(Auth::user()->isUser())
                    <td><input type="checkbox" class="bulk-select form-check" value="{{ $item->id }}" aria-label="Gerät auswählen"></td>
                    @endif
                    <td>{{ $item->nummer ?: '—' }}</td>
                    <td class="font-medium">
                        <a href="{{ route('items.show', $item->id) }}" class="text-gray-900 hover:text-orange-500">
                            {{ $item->bezeichnung }}
                        </a>
                    </td>
                    <td>{{ $item->lensDetail->manufacturer ?? '—' }}</td>
                    <td>{{ $item->lensDetail->model ?? '—' }}</td>
                    <td>{{ $item->lensDetail->zoom_factor ?? '—' }}</td>
                    <td>{{ $item->lensDetail->serial_number ?? '—' }}</td>
                    <td>{{ $item->supplier->bezeichnung ?? 'Eigentum' }}</td>
                    <td>@include('items.tables._actions')</td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="empty-state">Keine Objektive gefunden.</td>
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
    <div class="md:hidden space-y-4">
        @forelse($items as $item)
        <div class="relative card p-4" data-search="{{ $item->bezeichnung }} {{ $item->nummer }}" data-item-id="{{ $item->id }}">
            @if(Auth::user()->isUser())
            <input type="checkbox" class="bulk-select absolute top-3 right-3 form-check" value="{{ $item->id }}" aria-label="Gerät auswählen">
            @endif
            <a href="{{ route('items.show', $item->id) }}" class="block text-lg font-semibold text-gray-900 hover:text-orange-500">
                {{ $item->bezeichnung }}
            </a>
            <div class="mt-3 text-sm text-gray-700 space-y-1">
                <p><strong>Hersteller:</strong> {{ $item->lensDetail->manufacturer ?? '—' }}</p>
                <p><strong>Modell:</strong> {{ $item->lensDetail->model ?? '—' }}</p>
                <p><strong>Zoom:</strong> {{ $item->lensDetail->zoom_factor ?? '—' }}</p>
                <p><strong>SN:</strong> {{ $item->lensDetail->serial_number ?? '—' }}</p>
                <p><strong>Vermieter:</strong> {{ $item->supplier->bezeichnung ?? 'Eigentum' }}</p>
            </div>
            <div class="mt-4">@include('items.tables._actions')</div>
        </div>
        @empty
        <div class="card p-4 text-center text-gray-500">Keine Objektive gefunden.</div>
        @endforelse
    </div>

    <div data-search-empty class="hidden mt-4 card card-pad text-center text-gray-500">
        Keine Objektive gefunden.
    </div>

</div>
