{{-- Aktionen pro Zeile: Details, Bearbeiten, Zur Produktion hinzufügen --}}
{{-- Erwartet: $item, $productions --}}
<div class="flex justify-end items-center gap-2">

    @if($productions->count())
    <button type="button"
            onclick="openProductionDropdown({{ $item->id }}, {{ $item->units_id }}, this)"
            class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold py-1 px-3 rounded whitespace-nowrap">
        + Produktion
    </button>
    @endif

    <a href="{{ route('items.show', $item->id) }}"
       class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-1 px-3 rounded text-xs">
        Details
    </a>
    <a href="{{ route('items.edit', $item->id) }}"
       class="bg-orange-400 hover:bg-orange-500 text-white font-semibold py-1 px-3 rounded text-xs">
        Bearbeiten
    </a>
</div>
