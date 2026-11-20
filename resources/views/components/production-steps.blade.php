@props(['production', 'active' => 'details'])

@php
    $status = $production->stepStatus();
    $canCreate = auth()->user()?->isUser();
    $btn = fn (string $key) => $active === $key ? 'btn btn-primary btn-sm' : 'btn btn-secondary btn-sm';
    $inert = 'btn btn-secondary btn-sm cursor-default opacity-70 pointer-events-none';
    $current = fn (string $key) => $active === $key ? 'step' : null;
@endphp

<nav data-production-steps aria-label="Schritte für {{ $production->bezeichnung }}" class="flex flex-wrap gap-2">
    @if ($canCreate)
        <a href="{{ route('productions.edit', $production) }}" class="{{ $btn('details') }}" @if ($current('details')) aria-current="step" @endif>
            Details
        </a>
    @else
        <span class="{{ $inert }}" @if ($current('details')) aria-current="step" @endif>
            Details
        </span>
    @endif

    @if ($status['vb'])
        <a href="{{ route('vb-protokoll.show', $production) }}" class="{{ $btn('vb') }}" @if ($current('vb')) aria-current="step" @endif>
            VB-Protokoll <span class="badge badge-green">vorhanden</span>
        </a>
    @elseif ($canCreate)
        <a href="{{ route('vb-protokoll.create', $production) }}" class="{{ $btn('vb') }}" @if ($current('vb')) aria-current="step" @endif>
            VB-Protokoll <span class="badge badge-amber">fehlt</span>
        </a>
    @else
        <span class="{{ $inert }}" @if ($current('vb')) aria-current="step" @endif>
            VB-Protokoll <span class="badge badge-gray">fehlt</span>
        </span>
    @endif

    <a href="{{ route('productions.show', $production) }}" class="{{ $btn('packliste') }}" @if ($current('packliste')) aria-current="step" @endif>
        Packliste <span class="badge badge-gray">{{ $status['packliste'] }}</span>
    </a>

    <a href="{{ route('packvorgang.show', $production) }}" class="{{ $btn('packen') }}" @if ($current('packen')) aria-current="step" @endif>
        Packen
        <span class="badge {{ $status['confirmed'] ? 'badge-green' : 'badge-gray' }}" @if ($active === 'packen') x-text="packedCount + '/' + total" @endif>{{ $status['packed'] }}/{{ $status['packliste'] }}</span>
        @if ($status['confirmed'])
            <span class="badge badge-green">abgeschlossen</span>
        @endif
    </a>
</nav>
