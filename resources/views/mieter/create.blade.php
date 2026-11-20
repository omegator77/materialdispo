<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Neuen Mieter anlegen"></x-page-header>
    </x-slot>

    <div class="page-narrow">
        <form action="{{ route('mieter.store') }}" method="POST">
            @csrf

            @include('mieter._form')
        </form>
    </div>
</x-app-layout>
