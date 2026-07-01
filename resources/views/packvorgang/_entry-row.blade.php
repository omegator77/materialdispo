@php $item = $entry['item']; @endphp
<div class="flex items-start gap-3 border border-gray-200 rounded-lg p-3"
     :class="packs[{{ $item->id }}].packed ? 'bg-green-50 border-green-200' : 'bg-white'">
    <input type="checkbox"
           x-model="packs[{{ $item->id }}].packed"
           @change="toggle({{ $item->id }}, $event)"
           @if($locked) disabled @endif
           class="mt-1 h-5 w-5 rounded border-gray-300 text-orange-500 focus:ring-orange-400 disabled:opacity-50 disabled:cursor-not-allowed">

    <div class="min-w-0 flex-1">
        <div class="font-semibold text-gray-900">
            {{ $item->bezeichnung }}{{ $item->nummer ? ' ('.$item->nummer.')' : '' }}
        </div>

        <div class="text-xs text-gray-500 mt-0.5 flex flex-wrap items-center gap-2">
            @if($entry['source'] === 'kamera')
            <span class="px-1.5 py-0.5 rounded-full bg-orange-100 text-orange-700">{{ $entry['role'] }}</span>
            @endif

            <span x-show="packs[{{ $item->id }}].packed" x-cloak>
                gepackt von <span x-text="packs[{{ $item->id }}].by"></span>, <span x-text="packs[{{ $item->id }}].at"></span>
            </span>
        </div>

        @if(!empty($entry['notes']))
        <div class="mt-1 text-sm text-gray-600">
            {{ $entry['notes'] }}
        </div>
        @endif
    </div>
</div>
