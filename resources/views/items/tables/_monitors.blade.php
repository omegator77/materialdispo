@include('items.tables._filter')

<div class="page">

    {{-- Desktop --}}
    <div class="hidden md:block card overflow-hidden">
        <table class="data-table">
            <thead>
                <tr>
                    @if(Auth::user()->isUser())
                    <th class="w-8"><input type="checkbox" class="bulk-select-all rounded border-gray-300" title="Alle sichtbaren auswählen" aria-label="Alle sichtbaren auswählen"></th>
                    @endif
                    <th>
                        <a href="{{ route('items.index', array_merge(request()->all(), ['sort_by' => 'nummer', 'sort_direction' => request('sort_direction') == 'asc' ? 'desc' : 'asc'])) }}" class="hover:text-orange-500">Nr.</a>
                    </th>
                    <th>Hersteller</th>
                    <th>Modell</th>
                    <th>Größe</th>
                    <th>Audio</th>
                    <th>Format</th>
                    <th>Wandler</th>
                    <th>Standfuß</th>
                    <th class="text-right">Aktionen</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                <tr class="border-b hover:bg-gray-50" data-search="{{ $item->bezeichnung }} {{ $item->nummer }} {{ $item->monitorDetail->model ?? '' }}" data-item-id="{{ $item->id }}">
                    @if(Auth::user()->isUser())
                    <td><input type="checkbox" class="bulk-select rounded border-gray-300" value="{{ $item->id }}" aria-label="Gerät auswählen"></td>
                    @endif
                    <td>{{ $item->nummer ?: '—' }}</td>
                    <td>{{ $item->monitorDetail->manufacturer ?? '—' }}</td>
                    <td class="font-medium">
                        <a href="{{ route('items.show', $item->id) }}" class="text-gray-900 hover:text-orange-500">
                            {{ $item->monitorDetail->model ?? $item->bezeichnung }}
                        </a>
                    </td>
                    <td>{{ $item->monitorDetail->screen_size ?? '—' }}</td>
                    <td>
                        {{ (($item->monitorDetail->has_speakers ?? false) || ($item->monitorDetail->has_headphone ?? false)) ? 'Ja' : 'Nein' }}
                    </td>
                    <td>{{ $item->monitorDetail->max_input_format ?? '—' }}</td>
                    <td>{{ $item->monitorDetail->converter_model ?? '—' }}</td>
                    <td>{{ ($item->monitorDetail->has_stand ?? false) ? 'Ja' : 'Nein' }}</td>
                    <td>@include('items.tables._actions')</td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="empty-state">Keine Monitore gefunden.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Mobile --}}
    @if(Auth::user()->isUser())
    <label class="md:hidden flex items-center gap-2 text-sm text-gray-600 mb-2 px-1">
        <input type="checkbox" class="bulk-select-all rounded border-gray-300 w-5 h-5"> Alle sichtbaren auswählen
    </label>
    @endif
    <div class="md:hidden space-y-4">
        @forelse($items as $item)
        <div class="relative card p-4" data-search="{{ $item->bezeichnung }} {{ $item->nummer }} {{ $item->monitorDetail->model ?? '' }}" data-item-id="{{ $item->id }}">
            @if(Auth::user()->isUser())
            <input type="checkbox" class="bulk-select absolute top-3 right-3 w-6 h-6 rounded border-gray-300" value="{{ $item->id }}" aria-label="Gerät auswählen">
            @endif
            <a href="{{ route('items.show', $item->id) }}" class="block text-lg font-semibold text-gray-900 hover:text-orange-500">
                {{ $item->monitorDetail->model ?? $item->bezeichnung }}
            </a>
            <div class="mt-3 text-sm text-gray-700 space-y-1">
                <p><strong>Nr.:</strong> {{ $item->nummer ?: '—' }}</p>
                <p><strong>Hersteller:</strong> {{ $item->monitorDetail->manufacturer ?? '—' }}</p>
                <p><strong>Größe:</strong> {{ $item->monitorDetail->screen_size ?? '—' }}</p>
                <p><strong>Audio:</strong> {{ (($item->monitorDetail->has_speakers ?? false) || ($item->monitorDetail->has_headphone ?? false)) ? 'Ja' : 'Nein' }}</p>
                <p><strong>Format:</strong> {{ $item->monitorDetail->max_input_format ?? '—' }}</p>
                <p><strong>Wandler:</strong> {{ $item->monitorDetail->converter_model ?? '—' }}</p>
                <p><strong>Standfuß:</strong> {{ ($item->monitorDetail->has_stand ?? false) ? 'Ja' : 'Nein' }}</p>
            </div>
            <div class="mt-4">@include('items.tables._actions')</div>
        </div>
        @empty
        <div class="card p-4 text-center text-gray-500">Keine Monitore gefunden.</div>
        @endforelse
    </div>

    <div data-search-empty class="hidden mt-4 bg-white border border-gray-300 rounded-lg p-6 text-center text-gray-500">
        Keine Geräte gefunden.
    </div>

</div>
