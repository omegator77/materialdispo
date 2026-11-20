<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                VB-Protokoll anlegen
            </h2>

            <a href="{{ route('productions.show', $production->id) }}"
                class="inline-flex justify-center bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-4 rounded">
                Zurück
            </a>
        </div>
    </x-slot>

    <div class="max-w-4xl w-11/12 mx-auto mt-6 space-y-4">
        @if($templates->isNotEmpty())
        <div class="bg-white border border-orange-300 rounded-lg shadow-md p-4">
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
