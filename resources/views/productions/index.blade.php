<x-app-layout>
<x-slot name="header">
    <x-page-header title="Produktionen">
        <x-slot name="actions">
            @if(Auth::user()->isUser())
        <a href="{{ route('productions.create') }}"
               class="btn btn-primary">
                Neue Produktion
            </a>
        @endif
        </x-slot>
    </x-page-header>
</x-slot>

<x-flash />

@include('productions._table')





</x-app-layout>
