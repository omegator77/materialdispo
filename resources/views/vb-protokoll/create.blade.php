<x-app-layout>
    <x-slot name="header">
        <x-page-header title="VB-Protokoll anlegen">
            <x-slot name="actions">
                <a href="{{ route('productions.show', $production->id) }}"
                class="btn btn-secondary">
                Zurück
            </a>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="page">
        @if($templates->isNotEmpty())
        <div class="card p-4 border-orange-300">
            <h3 class="text-sm font-semibold text-gray-800">Aus früherer Produktion übernehmen</h3>
            <p class="text-xs text-gray-500 mb-3">Bedarf, Textblöcke und Crew-Stärken werden kopiert — Kunde und Ort bleiben leer. Ähnlichste Produktion zuerst.</p>
            <div class="flex flex-wrap gap-2">
                @foreach($templates as $t)
                <form method="POST" action="{{ route('vb-protokoll.fromTemplate', [$production, $t]) }}">
                    @csrf
                    <button type="submit" class="text-sm border border-gray-300 hover:border-orange-400 hover:bg-orange-50 rounded-full px-3 py-1.5">
                        {{ $t->bezeichnung }}
                        <span class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($t->booking_start)->format('m/Y') }}</span>
                    </button>
                </form>
                @endforeach
            </div>
        </div>
        @endif

        @include('vb-protokoll._form')
    </div>
</x-app-layout>
