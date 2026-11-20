<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Neue Gruppe anlegen"></x-page-header>
    </x-slot>

    <div class="page-narrow">
        <form action="{{ route('units.store') }}" method="POST">
            @csrf

            @include('units._form')
        </form>
    </div>
</x-app-layout>