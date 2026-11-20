<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Mieter bearbeiten"></x-page-header>
    </x-slot>

    <div class="page-narrow">
        <form action="{{ route('mieter.update', $mieter->id) }}" method="POST">
            @csrf
            @method('PUT')

            @include('mieter._form')
        </form>
    </div>
</x-app-layout>
