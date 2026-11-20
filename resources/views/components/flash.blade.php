{{-- Einheitliche Statusmeldungen (success / error / warning). Klassen bewusst ausgeschrieben (Tailwind-Purge). --}}
@php
    $styles = [
        'success' => 'bg-green-50 border-green-300 text-green-800',
        'warning' => 'bg-amber-50 border-amber-300 text-amber-800',
        'error' => 'bg-red-50 border-red-300 text-red-800',
    ];
@endphp
@foreach ($styles as $key => $classes)
    @if (session($key))
        <div class="max-w-7xl w-11/12 mx-auto mt-4 border px-4 py-3 rounded text-sm {{ $classes }}" role="status">
            {{ session($key) }}
        </div>
    @endif
@endforeach
