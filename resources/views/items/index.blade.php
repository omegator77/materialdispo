<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Geräte') }}
            </h2>
            <a href="{{ route('items.create') }}"
                class="inline-flex justify-center bg-orange-400 hover:bg-orange-500 text-white font-semibold py-2 px-4 rounded">
                Neues Gerät
            </a>
    </x-slot>
    <!-- @include('items._table') -->
    <!-- @include('items.tables._overview') -->


    @if((int) request('unit_id') === 1)
    @include('items.tables._cameras')
    @elseif((int) request('unit_id') === 2)
    @include('items.tables._lenses')
    @elseif(in_array((int) request('unit_id'), [9, 10], true))
    @include('items.tables._monitors')
    @else
    @include('items.tables._overview')
    @endif


</x-app-layout>