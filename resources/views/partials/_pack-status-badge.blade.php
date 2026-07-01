@php
$packlistCount = $production->packlistEntries()->count();
$packedCount = $production->packedItemIds()->count();

if ($packlistCount > 0) {
    if ($production->packvorgang_confirmed_at) {
        $badgeColor = 'bg-green-500';
    } elseif ($packedCount > 0) {
        $badgeColor = 'bg-yellow-500';
    } else {
        $badgeColor = 'bg-red-500';
    }
}
@endphp

@if($packlistCount > 0)
<span class="inline-block w-2.5 h-2.5 rounded-full {{ $badgeColor }}" title="Gepackt: {{ $packedCount }}/{{ $packlistCount }}{{ $production->packvorgang_confirmed_at ? ' – abgeschlossen' : '' }}"></span>
@endif
