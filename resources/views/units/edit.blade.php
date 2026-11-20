<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Gruppe bearbeiten"></x-page-header>
    </x-slot>

    <div class="page-narrow">
        <form action="{{ route('units.update', $unit->id) }}" method="POST">
            @csrf
            @method('PUT')

            @include('units._form')
        </form>
    </div>
</x-app-layout>