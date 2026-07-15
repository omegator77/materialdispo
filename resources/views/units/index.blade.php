<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Gruppen
            </h2>

            @if(Auth::user()->isUser())
            <a href="{{ route('units.create') }}"
               class="inline-flex justify-center bg-orange-400 hover:bg-orange-500 text-white font-semibold py-2 px-4 rounded">
                Neue Gruppe
            </a>
            @endif
        </div>
    </x-slot>

    @if(session('success') || session('error'))
    <div class="max-w-7xl w-11/12 mx-auto mt-6">
        @if(session('success'))
        <div class="bg-green-50 border border-green-300 text-green-700 px-4 py-3 rounded">
            {{ session('success') }}
        </div>
        @endif
        @if(session('error'))
        <div class="bg-red-50 border border-red-300 text-red-700 px-4 py-3 rounded">
            {{ session('error') }}
        </div>
        @endif
    </div>
    @endif

    @include('units._table')

</x-app-layout>