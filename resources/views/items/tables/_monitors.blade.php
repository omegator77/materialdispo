@include('items.tables._filter')

<div class="max-w-7xl w-11/12 mx-auto mt-6">

    {{-- Desktop --}}
    <div class="hidden md:block bg-white border border-gray-300 rounded-lg shadow-md overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-100 border-b">
                <tr>
                    <th class="text-left px-4 py-3">Nr.</th>
                    <th class="text-left px-4 py-3">Hersteller</th>
                    <th class="text-left px-4 py-3">Modell</th>
                    <th class="text-left px-4 py-3">Größe</th>
                    <th class="text-left px-4 py-3">Audio</th>
                    <th class="text-left px-4 py-3">Format</th>
                    <th class="text-left px-4 py-3">Wandler</th>
                    <th class="text-left px-4 py-3">Standfuß</th>
                    <th class="text-right px-4 py-3">Aktionen</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                <tr class="border-b hover:bg-gray-50">
                    <td class="px-4 py-3">{{ $item->nummer ?: '—' }}</td>
                    <td class="px-4 py-3">{{ $item->monitorDetail->manufacturer ?? '—' }}</td>
                    <td class="px-4 py-3 font-medium">
                        <a href="{{ route('items.show', $item->id) }}" class="text-gray-900 hover:text-orange-500">
                            {{ $item->monitorDetail->model ?? $item->bezeichnung }}
                        </a>
                    </td>
                    <td class="px-4 py-3">{{ $item->monitorDetail->screen_size ?? '—' }}</td>
                    <td class="px-4 py-3">
                        {{ (($item->monitorDetail->has_speakers ?? false) || ($item->monitorDetail->has_headphone ?? false)) ? 'Ja' : 'Nein' }}
                    </td>
                    <td class="px-4 py-3">{{ $item->monitorDetail->max_input_format ?? '—' }}</td>
                    <td class="px-4 py-3">{{ $item->monitorDetail->converter_model ?? '—' }}</td>
                    <td class="px-4 py-3">{{ ($item->monitorDetail->has_stand ?? false) ? 'Ja' : 'Nein' }}</td>
                    <td class="px-4 py-3">@include('items.tables._actions')</td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center py-6 text-gray-500">Keine Monitore gefunden.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Mobile --}}
    <div class="md:hidden space-y-4">
        @forelse($items as $item)
        <div class="bg-white border border-gray-300 rounded-lg shadow-md p-4">
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
        <div class="bg-white border border-gray-300 rounded-lg shadow-md p-4 text-center text-gray-500">Keine Monitore gefunden.</div>
        @endforelse
    </div>

</div>
