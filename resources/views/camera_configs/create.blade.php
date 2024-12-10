<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Kamera-Konfiguration hinzufügen
        </h2>
    </x-slot>

    <div class="max-w-4xl mx-auto mt-8 bg-white p-6 rounded-md shadow-md">
        <form method="POST" action="{{ route('camera-config.store', [$production->id, $item->id]) }}">
            @csrf

            <div class="mb-4">
                <label for="cam_number" class="block font-semibold">Kameranummer:</label>
                <input type="text" name="cam_number" id="cam_number" class="w-full border rounded p-2" value="{{ old('cam_number') }}" required>
            </div>

            <div class="mb-4">
                <label for="cam_position" class="block font-semibold">Kameraposition:</label>
                <input type="text" name="cam_position" id="cam_position" class="w-full border rounded p-2" value="{{ old('cam_position') }}">
            </div>

            <div class="mb-4">
                <label for="lens" class="block font-semibold">Objektiv:</label>
                <input type="text" name="lens" id="lens" class="w-full border rounded p-2" value="{{ old('lens') }}">
            </div>

            <div class="mb-4">
                <label for="tripod" class="block font-semibold">Stativ:</label>
                <input type="text" name="tripod" id="tripod" class="w-full border rounded p-2" value="{{ old('tripod') }}">
            </div>

            <div class="mb-4">
                <label for="tripod_head" class="block font-semibold">Stativkopf:</label>
                <input type="text" name="tripod_head" id="tripod_head" class="w-full border rounded p-2" value="{{ old('tripod_head') }}">
            </div>

            <div class="mb-4">
                <label for="large_lens_adapter" class="block font-semibold">Großer Objektivadapter:</label>
                <input type="text" name="large_lens_adapter" id="large_lens_adapter" class="w-full border rounded p-2" value="{{ old('large_lens_adapter') }}">
            </div>

            <div class="mb-4">
                <label for="notes" class="block font-semibold">Notizen:</label>
                <textarea name="notes" id="notes" class="w-full border rounded p-2">{{ old('notes') }}</textarea>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">
                    Speichern
                </button>
                <a href="{{ route('productions.show', $production->id) }}" class="ml-4 bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
                    Abbrechen
                </a>
            </div>
        </form>
    </div>
</x-app-layout>
