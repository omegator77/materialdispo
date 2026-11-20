<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Vermieter bearbeiten"></x-page-header>
    </x-slot>

    <div class="page-narrow">
        <form action="{{ route('suppliers.update', $supplier->id) }}" method="POST">
            @csrf
            @method('PUT')

            @include('suppliers._form')
        </form>
    </div>
</x-app-layout>