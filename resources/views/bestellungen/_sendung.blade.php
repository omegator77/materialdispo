@if($bestellung->sendungsnummer)
    @if($url = $bestellung->trackingUrl())
        <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="link">{{ \App\Models\Bestellung::CARRIERS[$bestellung->versanddienstleister] ?? '' }} {{ $bestellung->sendungsnummer }}</a>
    @else
        {{ trim((\App\Models\Bestellung::CARRIERS[$bestellung->versanddienstleister ?? ''] ?? '').' '.$bestellung->sendungsnummer) }}
    @endif
@else
    —
@endif
