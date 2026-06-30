@forelse($lastActivities as $activity)
    <div class="text-xs text-gray-600 leading-snug {{ !$loop->last ? 'mb-1.5' : '' }}">
        <span class="font-medium text-gray-900">{{ $activity->causer?->name ?? 'System' }}</span>
        {{ $activity->description }}
        <span class="text-gray-400">· {{ $activity->created_at->diffForHumans() }}</span>
    </div>
@empty
    <div class="text-xs text-gray-400">Noch keine Aktivitäten erfasst.</div>
@endforelse
