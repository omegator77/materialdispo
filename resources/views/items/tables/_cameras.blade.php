{{-- Filter --}}
<div class="max-w-7xl w-11/12 mx-auto mt-6">
    <div class="bg-white border border-gray-300 rounded-lg shadow-md p-4">
        <form method="GET" action="{{ route('items.index') }}">
            <div class="flex flex-col sm:flex-row sm:items-end gap-4">
                <div class="w-full sm:w-72">
                    <label for="unitFilter" class="block text-sm font-medium text-gray-700 mb-1">
                        Gruppe filtern
                    </label>

                    <select
                        id="unitFilter"
                        name="unit_id"
                        class="form-control w-full"
                        onchange="this.form.submit()">
                        <option value="">Alle Gruppen</option>

                        @foreach($units as $unit)
                        <option value="{{ $unit->id }}"
                            {{ (request('unit_id') ?? '') == $unit->id ? 'selected' : '' }}>
                            {{ $unit->bezeichnung }}
                        </option>
                        @endforeach
                    </select>
                </div>

                @if(request('unit_id'))
                <div>
                    <a href="{{ route('items.index') }}"
                        class="inline-flex justify-center bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-4 rounded">
                        Filter zurücksetzen
                    </a>
                </div>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="max-w-7xl w-11/12 mx-auto mt-6">

    <div class="hidden md:block bg-white border border-gray-300 rounded-lg shadow-md overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-100 border-b">
                <tr>
                    <th class="text-left px-4 py-3">Nr.</th>
                    <th class="text-left px-4 py-3">Bezeichnung</th>
                    <th class="text-left px-4 py-3">Body SN</th>
                    <th class="text-left px-4 py-3">Großer Sucher</th>
                    <th class="text-left px-4 py-3">Kleiner Sucher</th>
                    <th class="text-left px-4 py-3">SSL</th>
                    <th class="text-left px-4 py-3">Vermieter</th>
                    <th class="text-right px-4 py-3">Aktionen</th>
                </tr>
            </thead>

            <tbody>
                @forelse($items as $item)
                    <tr class="border-b hover:bg-gray-50">

                        <td class="px-4 py-3">
                            {{ $item->nummer ?: '—' }}
                        </td>

                        <td class="px-4 py-3 font-medium">
                            <a href="{{ route('items.show', $item->id) }}"
                               class="text-gray-900 hover:text-orange-500">
                                {{ $item->bezeichnung }}
                            </a>
                        </td>

                        <td class="px-4 py-3">
                            {{ $item->cameraDetail->body_serial ?? '—' }}
                        </td>

                        <td class="px-4 py-3">
                            @if($item->cameraDetail?->large_viewfinder_model || $item->cameraDetail?->large_viewfinder_type)
                                {{ $item->cameraDetail->large_viewfinder_model }}
                                @if($item->cameraDetail->large_viewfinder_type)
                                    <span class="text-gray-500">
                                        ({{ $item->cameraDetail->large_viewfinder_type }})
                                    </span>
                                @endif
                            @else
                                —
                            @endif
                        </td>

                        <td class="px-4 py-3">
                            @if($item->cameraDetail?->small_viewfinder_model || $item->cameraDetail?->small_viewfinder_type)
                                {{ $item->cameraDetail->small_viewfinder_model }}
                                @if($item->cameraDetail->small_viewfinder_type)
                                    <span class="text-gray-500">
                                        ({{ $item->cameraDetail->small_viewfinder_type }})
                                    </span>
                                @endif
                            @else
                                —
                            @endif
                        </td>

                        <td class="px-4 py-3">
                            {{ ($item->cameraDetail->ssl_license ?? false) ? 'Ja' : 'Nein' }}
                        </td>

                        <td class="px-4 py-3">
                            {{ $item->supplier->bezeichnung ?? 'Eigentum' }}
                        </td>

                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('items.show', $item->id) }}"
                                   class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-1 px-3 rounded">
                                    Details
                                </a>

                                <a href="{{ route('items.edit', $item->id) }}"
                                   class="bg-orange-400 hover:bg-orange-500 text-white font-semibold py-1 px-3 rounded">
                                    Bearbeiten
                                </a>
                            </div>
                        </td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-6 text-gray-500">
                            Keine Kameras gefunden.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Mobile Kartenansicht --}}
<div class="md:hidden space-y-4">
    @forelse($items as $item)
        <div class="bg-white border border-gray-300 rounded-lg shadow-md p-4">
            <a href="{{ route('items.show', $item->id) }}"
               class="block text-lg font-semibold text-gray-900 hover:text-orange-500">
                {{ $item->bezeichnung }}
            </a>

           <div class="mt-3 text-sm text-gray-700 space-y-1">
    <p><strong>Nr.:</strong> {{ $item->nummer ?: '—' }}</p>
    <p><strong>Body SN:</strong> {{ $item->cameraDetail->body_serial ?? '—' }}</p>

    <p>
        <strong>Großer Sucher:</strong>
        @if($item->cameraDetail?->large_viewfinder_model || $item->cameraDetail?->large_viewfinder_type)
            {{ $item->cameraDetail->large_viewfinder_model }}
            @if($item->cameraDetail->large_viewfinder_type)
                <span class="text-gray-500">
                    ({{ $item->cameraDetail->large_viewfinder_type }})
                </span>
            @endif
        @else
            —
        @endif
    </p>

    <p>
        <strong>Kleiner Sucher:</strong>
        @if($item->cameraDetail?->small_viewfinder_model || $item->cameraDetail?->small_viewfinder_type)
            {{ $item->cameraDetail->small_viewfinder_model }}
            @if($item->cameraDetail->small_viewfinder_type)
                <span class="text-gray-500">
                    ({{ $item->cameraDetail->small_viewfinder_type }})
                </span>
            @endif
        @else
            —
        @endif
    </p>

    <p><strong>SSL:</strong> {{ ($item->cameraDetail->ssl_license ?? false) ? 'Ja' : 'Nein' }}</p>
    <p><strong>Vermieter:</strong> {{ $item->supplier->bezeichnung ?? 'Eigentum' }}</p>
</div>

            <div class="mt-4 flex gap-2">
                <a href="{{ route('items.show', $item->id) }}"
                   class="flex-1 text-center bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-3 rounded">
                    Details
                </a>

                <a href="{{ route('items.edit', $item->id) }}"
                   class="flex-1 text-center bg-orange-400 hover:bg-orange-500 text-white font-semibold py-2 px-3 rounded">
                    Bearbeiten
                </a>
            </div>
        </div>
    @empty
        <div class="bg-white border border-gray-300 rounded-lg shadow-md p-4 text-center text-gray-500">
            Keine Kameras gefunden.
        </div>
    @endforelse
</div>

</div>