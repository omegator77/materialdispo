<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Aktivitätsprotokoll"></x-page-header>
    </x-slot>

    <div class="page">

        <div class="card overflow-hidden">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Zeitpunkt</th>
                        <th>Benutzer</th>
                        <th>Aktion</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($activities as $activity)
                    <tr class="align-top">
                        <td class="px-4 py-3 text-gray-500 whitespace-nowrap">
                            {{ $activity->created_at->format('d.m.Y H:i') }}
                        </td>
                        <td class="font-medium">
                            {{ $activity->causer?->name ?? 'System' }}
                        </td>
                        <td>
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
                        <td>
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
                        <td colspan="4" class="empty-state">Noch keine Aktivitäten erfasst.</td>
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
