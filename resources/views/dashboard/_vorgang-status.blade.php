@php
    $colors = [
        0 => 'border-l-red-400',
        1 => 'border-l-orange-400',
        2 => 'border-l-yellow-400',
        3 => 'border-l-lime-400',
    ];
    $borderClass = $colors[$entry['doneCount']] ?? $colors[0];
@endphp

<details class="mb-2 rounded-lg border border-l-4 border-gray-200 {{ $borderClass }}">
    <summary class="cursor-pointer px-3 py-2 flex items-center justify-between gap-2 list-none">
        <div class="flex items-center gap-2 min-w-0">
            <span class="badge {{ $entry['badgeClass'] }} shrink-0">{{ $entry['badge'] }}</span>
            <span class="font-medium text-gray-900 truncate">{{ $entry['title'] }}</span>
            <span class="text-sm text-gray-500 truncate hidden sm:inline">
                {{ \Carbon\Carbon::parse($entry['periodStart'])->format('d.m.Y') }}–{{ \Carbon\Carbon::parse($entry['periodEnd'])->format('d.m.Y') }}
            </span>
        </div>
        <span class="text-xs text-gray-500 shrink-0">{{ $entry['doneCount'] }}/{{ count($entry['checks']) }}</span>
    </summary>

    <div class="px-3 pb-3 pt-1 space-y-2">
        <a href="{{ $entry['showRoute'] }}" class="link text-xs">
            Zum Vorgang →
        </a>

        @foreach($entry['checks'] as $check)
        <div class="flex items-center justify-between rounded-lg border border-gray-200 px-3 py-2">
            <span class="text-sm text-gray-700">{{ $check['label'] }}</span>

            @if($check['done'])
                <form action="{{ $check['reopenRoute'] }}" method="POST">
                    @csrf
                    @if(($check['reopenMethod'] ?? 'DELETE') !== 'POST')
                        @method($check['reopenMethod'] ?? 'DELETE')
                    @endif
                    <button type="submit" class="text-xs text-gray-500 hover:underline">✓ Wieder öffnen</button>
                </form>
            @else
                <form action="{{ $check['confirmRoute'] }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-sm">
                        Markieren
                    </button>
                </form>
            @endif
        </div>
        @endforeach
    </div>
</details>
