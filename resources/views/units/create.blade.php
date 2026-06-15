<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Neue Gruppe anlegen
        </h2>
    </x-slot>

    <div class="max-w-5xl w-11/12 mx-auto mt-6">
        <form action="{{ route('units.store') }}" method="POST">
            @csrf

            @include('units._form')
        </form>
    </div>
</x-app-layout>