<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Aktivitätsprotokoll</h2>
    </x-slot>

    <div class="max-w-6xl w-11/12 mx-auto mt-6">

        <div class="bg-white border border-gray-300 rounded-lg shadow-md overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-100 border-b">
                    <tr>
                        <th class="text-left px-4 py-3">Zeitpunkt</th>
                        <th class="text-left px-4 py-3">Benutzer</th>
                        <th class="text-left px-4 py-3">Aktion</th>
                        <th class="text-left px-4 py-3">Details</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($activities as $activity)
                    <tr class="border-b hover:bg-gray-50 align-top">
                        <td class="px-4 py-3 text-gray-500 whitespace-nowrap">
                            {{ $activity->created_at->format('d.m.Y H:i') }}
                        </td>
                        <td class="px-4 py-3 font-medium">
                            {{ $activity->causer?->name ?? 'System' }}
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $eventBadge = match($activity->event) {
                                    'created'  => ['bg-green-100 text-green-700', 'Angelegt'],
                                    'updated'  => ['bg-blue-100 text-blue-700', 'Geändert'],
                                    'deleted'  => ['bg-red-100 text-red-700', 'Gelöscht'],
                                    'attached' => ['bg-blue-100 text-blue-700', 'Hinzugefügt'],
                                    'detached' => ['bg-orange-100 text-orange-700', 'Entfernt'],
                                    default    => ['bg-gray-100 text-gray-600', $activity->event ?? '—'],
                                };
                            @endphp
                            <span class="inline-block px-2 py-0.5 rounded text-xs font-semibold {{ $eventBadge[0] }}">
                                {{ $eventBadge[1] }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-700">
                            {{ $activity->description }}

                            @if($activity->event === 'updated' && $activity->properties->has('attributes'))
                                <div class="mt-1 text-xs text-gray-500 space-y-0.5">
                                    @foreach($activity->properties->get('attributes') as $field => $newValue)
                                        @php $oldValue = $activity->properties->get('old')[$field] ?? null; @endphp
                                        <div>
                                            <span class="font-medium">{{ $field }}:</span>
                                            {{ $oldValue ?? '—' }} → {{ $newValue ?? '—' }}
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center py-6 text-gray-500">Noch keine Aktivitäten erfasst.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $activities->links() }}
        </div>
    </div>
</x-app-layout>
