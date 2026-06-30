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

    <div class="max-w-4xl w-11/12 mx-auto mt-6">
        @include('vb-protokoll._form')
    </div>
</x-app-layout>
