{{-- Mietzeitraum eines Mietgeräts: laufender/nächster offener Mietvorgang (Item::displayMietvorgang()), weitere dahinter;
     ohne Mietvorgang "noch nicht gemietet"; abgelaufen und nicht zurückgegeben: "Rückgabe offen". Erwartet: $item --}}
@php($angezeigt = $item->displayMietvorgang())
@if($angezeigt)
    {{ $angezeigt->rent_start->format('d.m.Y') }} – {{ $angezeigt->rent_end->format('d.m.Y') }}
    @php($weitere = $item->mietvorgaenge->reject(fn ($mv) => $mv->id === $angezeigt->id)->sortBy('rent_start')->values())
    @if($weitere->isNotEmpty())
        <details class="inline">
            <summary class="inline cursor-pointer text-orange-600 hover:underline text-xs">+{{ $weitere->count() }} weitere</summary>
            <span class="block mt-0.5">
                @foreach($weitere as $mv)
                    {{ $mv->rent_start->format('d.m.Y') }} – {{ $mv->rent_end->format('d.m.Y') }}@if(!$loop->last), @endif
                @endforeach
            </span>
        </details>
    @endif
    @if($item->hasOpenReturn())
        <span class="badge badge-red">Rückgabe offen</span>
    @endif
@else
    <span class="badge badge-amber">noch nicht gemietet</span>
@endif
