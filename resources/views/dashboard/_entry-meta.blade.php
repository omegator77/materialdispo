@php
    $items = $model->items;
    $dateLabel = $mode === 'running'
        ? \Carbon\Carbon::parse($model->rent_start)->format('d.m.Y').' – '.\Carbon\Carbon::parse($model->rent_end)->format('d.m.Y')
        : 'Start: '.\Carbon\Carbon::parse($model->rent_start)->format('d.m.Y');
@endphp

<details class="group text-sm text-gray-500">
    <summary class="flex items-center gap-1 cursor-pointer select-none list-none">
        <span class="shrink-0">{{ $dateLabel }}</span>
        @if($items->isNotEmpty())
            <span class="truncate min-w-0">&middot; {{ $items->pluck('bezeichnung')->implode(', ') }}</span>
        @endif
        <svg class="h-3.5 w-3.5 text-gray-400 shrink-0 ml-auto group-open:rotate-180 transition-transform"
             viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd"
                  d="M5.23 7.21a.75.75 0 011.06.02L10 11.172l3.71-3.94a.75.75 0 011.08 1.04l-4.24 4.5a.75.75 0 01-1.08 0l-4.24-4.5a.75.75 0 01.02-1.06z"
                  clip-rule="evenodd" />
        </svg>
    </summary>

    <div class="mt-1">
        @forelse($items as $item)
            {{ $item->bezeichnung }} @if($item->nummer)<span class="text-gray-400">({{ $item->nummer }})</span>@endif{{ $loop->last ? '' : ', ' }}
        @empty
            <span class="italic text-gray-400">Keine Geräte zugeordnet</span>
        @endforelse
    </div>
</details>
