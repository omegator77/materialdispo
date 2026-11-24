@php
    $badge = match ($bestellung->stufe()) {
        'abgelegt' => 'badge-green',
        'rechnung' => 'badge-amber',
        'unterwegs' => 'badge-blue',
        default => 'badge-gray',
    };
@endphp
<span class="badge {{ $badge }}">{{ $bestellung->stufeLabel() }}</span>
