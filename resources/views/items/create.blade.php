<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Neues Material anlegen"></x-page-header>
    </x-slot>

    <div class="page-narrow">
        <form action="{{ route('items.store') }}" method="POST">
            @csrf

            @include('items._form')
        </form>
    </div>

   
</x-app-layout>