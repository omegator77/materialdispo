@php
    $colors = [
        0 => 'border-red-400',
        1 => 'border-orange-400',
        2 => 'border-yellow-400',
        3 => 'border-lime-400',
    ];
    $borderClass = $colors[$entry['doneCount']] ?? $colors[0];
@endphp

<details class="border-l-4 {{ $borderClass }} border-t border-r border-b border-gray-200 rounded mb-2">
    <summary class="cursor-pointer px-3 py-2 flex items-center justify-between gap-2 list-none">
        <div class="flex items-center gap-2 min-w-0">
            <span class="inline-block {{ $entry['badgeClass'] }} text-xs px-2 py-0.5 rounded-full shrink-0">{{ $entry['badge'] }}</span>
            <span class="font-medium text-gray-900 truncate">{{ $entry['title'] }}</span>
            <span class="text-sm text-gray-500 truncate hidden sm:inline">
                {{ \Carbon\Carbon::parse($entry['model']->rent_start)->format('d.m.Y') }}–{{ \Carbon\Carbon::parse($entry['model']->rent_end)->format('d.m.Y') }}
            </span>
        </div>
        <span class="text-xs text-gray-500 shrink-0">{{ $entry['doneCount'] }}/4</span>
    </summary>

    <div class="px-3 pb-3 pt-1 space-y-2">
        <a href="{{ $entry['showRoute'] }}" class="text-xs text-orange-600 hover:underline">
            Zum Vorgang →
        </a>

        @foreach($entry['checks'] as $check)
        <div class="flex items-center justify-between border border-gray-200 rounded px-3 py-2">
            <span class="text-sm text-gray-700">{{ $check['label'] }}</span>

            @if($check['done'])
                <form action="{{ $check['reopenRoute'] }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-xs text-gray-500 hover:underline">✓ Wieder öffnen</button>
                </form>
            @else
                <form action="{{ $check['confirmRoute'] }}" method="POST">
                    @csrf
                    <button type="submit" class="bg-orange-400 hover:bg-orange-500 text-white text-xs font-semibold py-1 px-2 rounded">
                        Markieren
                    </button>
                </form>
            @endif
        </div>
        @endforeach
    </div>
</details>
