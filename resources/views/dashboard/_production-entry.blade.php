@php
    $kind = $entry['kind'];
    $model = $entry['model'];
@endphp

@if($kind === 'production')
    <a href="{{ route('productions.show', $model->id) }}" class="block border-b last:border-b-0 py-3 hover:bg-gray-50">
        <div class="font-medium text-gray-900 flex items-center gap-2">
            @include('partials._pack-status-badge', ['production' => $model])
            {{ $model->bezeichnung }}
        </div>
        <div class="text-sm text-gray-500">
            @if($mode === 'running')
                {{ \Carbon\Carbon::parse($model->booking_start)->format('d.m.Y') }}
                –
                {{ \Carbon\Carbon::parse($model->booking_end)->format('d.m.Y') }}
            @else
                Start: {{ \Carbon\Carbon::parse($model->booking_start)->format('d.m.Y') }}
            @endif
        </div>
    </a>
@elseif($kind === 'mietvorgang')
    <div class="border-b last:border-b-0 py-3">
        <a href="{{ route('mietvorgaenge.show', $model) }}" class="block hover:bg-gray-50">
            <div class="font-medium text-gray-900 flex items-center gap-2">
                <span class="inline-block bg-amber-50 text-amber-700 text-xs px-2 py-0.5 rounded-full shrink-0">Miete</span>
                {{ $model->bezeichnung ?? $model->supplier?->bezeichnung ?? 'Vermieter gelöscht' }}
            </div>
        </a>
        @include('dashboard._entry-meta', ['model' => $model, 'mode' => $mode])
    </div>
@else
    <div class="border-b last:border-b-0 py-3">
        <a href="{{ route('vermietvorgaenge.show', $model) }}" class="block hover:bg-gray-50">
            <div class="font-medium text-gray-900 flex items-center gap-2">
                <span class="inline-block bg-purple-50 text-purple-700 text-xs px-2 py-0.5 rounded-full shrink-0">Verleih</span>
                {{ $model->bezeichnung ?? $model->mieter?->bezeichnung ?? 'Mieter gelöscht' }}
            </div>
        </a>
        @include('dashboard._entry-meta', ['model' => $model, 'mode' => $mode])
    </div>
@endif
