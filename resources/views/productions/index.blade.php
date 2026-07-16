<x-app-layout>
<x-slot name="header">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Productions') }}
        </h2>
        @if(Auth::user()->isUser())
        <a href="{{ route('productions.create') }}"
               class="inline-flex justify-center bg-orange-400 hover:bg-orange-500 text-white font-semibold py-2 px-4 rounded">
                Neue Produktion
            </a>
        @endif
    </div>
    </x-slot>

@include('productions._table')





</x-app-layout>
