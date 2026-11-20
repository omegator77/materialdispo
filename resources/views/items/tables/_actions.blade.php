{{-- Aktionen pro Zeile: Details, Bearbeiten, Zur Produktion hinzufügen --}}
{{-- Erwartet: $item, $productions --}}
<div class="flex justify-end items-center gap-2">

    @if(Auth::user()->isUser() && $productions->count())
    <button type="button"
            onclick="openProductionDropdown({{ $item->id }}, {{ $item->units_id }}, this)"
            class="btn btn-soft btn-sm whitespace-nowrap">
        + Produktion
    </button>
    @endif

    <a href="{{ route('items.show', $item->id) }}"
       class="btn btn-secondary btn-sm">
        Details
    </a>

    @if(Auth::user()->isUser())
    <a href="{{ route('items.edit', $item->id) }}"
       class="btn btn-primary btn-sm">
        Bearbeiten
    </a>
    @endif
</div>
