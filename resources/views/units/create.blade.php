<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Gruppen') }}
        </h2>
    </x-slot>

    <div class="max-w-7xl w-4/5  mx-auto mt-6 bg-white p-6 border border-gray-400 rounded-md shadow-md">
        <form action="/units" method="POST" class="space-y-6">
            @csrf
            <div class="flex flex-wrap gap-4">
            <div class="w-full md:flex-1">
                <label for="bezeichnung" class="block text-sm font-medium text-gray-700">Bezeichnung:</label>
                <input
                    type="text"
                    name="bezeichnung"
                    id="bezeichnung"
                    placeholder="Bezeichnung ..."
                    value="{{ old('bezeichnung') }}"
                    class="mt-1 p-2 block w-full border border-gray-300 rounded-md focus:outline-none focus:ring focus:border-blue-300"
                >
            </div>

            <div class="w-full md:flex-1">
                <label for="description" class="block text-sm font-medium text-gray-700">Bemerkung:</label>
                <input
                    type="text"
                    name="description"
                    id="description"
                    placeholder="Bemerkung ..."
                    value="{{ old('description') }}"
                    class="mt-1 p-2 block w-full border border-gray-300 rounded-md focus:outline-none focus:ring focus:border-blue-300"
                >
            </div>
            </div>

            <div class="text-right">
                <button type="submit"
                    class="bg-orange-400 hover:bg-orange-500 text-white font-thin hover:font-extrabold py-1 px-4 rounded focus:outline-none focus:ring">
                    Gruppe Speichern
                </button>
            </div>

            @if ($errors->any())
                <div class="text-red-600">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </form>
    </div>

    <div class="max-w-7xl mx-auto mt-6">
        @include('units._table')
    </div>
</x-app-layout>
