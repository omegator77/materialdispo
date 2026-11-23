{{-- Mietzeitraum eines Mietgeräts aus seinen Mietvorgängen; ohne Mietvorgang "noch nicht gemietet". Erwartet: $item --}}
@php($vorgaenge = $item->mietvorgaenge->sortBy('rent_start')->values())
@if($vorgaenge->isNotEmpty())
    {{ $vorgaenge->first()->rent_start->format('d.m.Y') }} – {{ $vorgaenge->first()->rent_end->format('d.m.Y') }}
    @if($vorgaenge->count() > 1)
        <details class="inline">
            <summary class="inline cursor-pointer text-orange-600 hover:underline text-xs">+{{ $vorgaenge->count() - 1 }} weitere</summary>
            <span class="block mt-0.5">
                @foreach($vorgaenge->slice(1) as $mv)
                    {{ $mv->rent_start->format('d.m.Y') }} – {{ $mv->rent_end->format('d.m.Y') }}@if(!$loop->last), @endif
                @endforeach
            </span>
        </details>
    @endif
@else
    <span class="badge badge-amber">noch nicht gemietet</span>
@endif
