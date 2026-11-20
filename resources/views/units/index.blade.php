<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Gruppen">
            <x-slot name="actions">
                @if(Auth::user()->isUser())
            <a href="{{ route('units.create') }}"
               class="btn btn-primary">
                Neue Gruppe
            </a>
            @endif
            </x-slot>
        </x-page-header>
    </x-slot>

    @if(session('success') || session('error'))
    <div class="page">
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