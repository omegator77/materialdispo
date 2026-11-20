<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Neuen Vermieter anlegen"></x-page-header>
    </x-slot>

    <div class="page-narrow">
        <form action="{{ route('suppliers.store') }}" method="POST">
            @csrf

            @include('suppliers._form')
        </form>
    </div>
</x-app-layout>