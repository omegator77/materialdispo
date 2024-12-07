<x-app-layout>
<x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Einheiten') }}
        </h2>
    </x-slot>
    @include('items._table')
    
    <div class="w-4/5 mx-auto pt-4 text-right"><a href="items/create" class="bg-orange-400 hover:bg-orange-500 hover:font-extrabold text-white font-thin py-1 px-4 rounded focus:outline-none focus:ring">
            Neue  Einheit
            </a></div>
</x-app-layout>