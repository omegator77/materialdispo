{{--
    Einheitliche Statusmeldungen (success / warning / error). Klassen bewusst ausgeschrieben (Tailwind-Purge).
    Steht die Komponente schon in einem .page-Container: <x-flash contained />.
--}}
@props(['contained' => false])
@php
    $styles = [
        'success' => 'border-green-200 bg-green-50 text-green-800',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-800',
        'error' => 'border-red-200 bg-red-50 text-red-800',
    ];
    $active = collect($styles)->filter(fn ($classes, $key) => session($key));
@endphp
@if ($active->isNotEmpty())
    @unless ($contained)
        <div class="page pb-0">
    @endunless
    <div class="space-y-3">
        @foreach ($active as $key => $classes)
            <div class="rounded-lg border px-4 py-3 text-sm {{ $classes }}" role="status">{{ session($key) }}</div>
        @endforeach
    </div>
    @unless ($contained)
        </div>
    @endunless
@endif
