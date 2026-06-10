<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Kamera-Konfiguration bearbeiten
        </h2>
    </x-slot>

    <div class="max-w-4xl mx-auto mt-6 bg-white p-6 border border-gray-300 rounded-md shadow-md">
        <form action="{{ route('camera-config.update', $config->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <div>
                    <label for="cam_number" class="block font-semibold mb-1">Kamera-Nummer</label>
                    <input
                        type="text"
                        name="cam_number"
                        id="cam_number"
                        value="{{ old('cam_number', $config->cam_number) }}"
                        class="w-full p-2 border border-gray-300 rounded"
                        required
                    >
                </div>

                <div>
                    <label for="camera" class="block font-semibold mb-1">Kamera</label>
                    <select name="camera" id="camera" class="w-full p-2 border border-gray-300 rounded" required>
                        @foreach ($cameras as $camera)
                            <option value="{{ $camera->id }}" @selected(old('camera', $config->item_id) == $camera->id)>
                                {{ $camera->bezeichnung }} ({{ $camera->nummer }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="lens" class="block font-semibold mb-1">Objektiv</label>
                    <select name="lens" id="lens" class="w-full p-2 border border-gray-300 rounded">
                        <option value="">— kein Objektiv —</option>
                        @foreach ($lenses as $lens)
                            <option value="{{ $lens->id }}" @selected(old('lens', $config->lens) == $lens->id)>
                                {{ $lens->bezeichnung }} ({{ $lens->nummer }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="large_lens_adapter" class="block font-semibold mb-1">Large-Lens-Adapter</label>
                    <select name="large_lens_adapter" id="large_lens_adapter" class="w-full p-2 border border-gray-300 rounded">
                        <option value="">— kein Adapter —</option>
                        @foreach ($adapters as $adapter)
                            <option value="{{ $adapter->id }}" @selected(old('large_lens_adapter', $config->large_lens_adapter) == $adapter->id)>
                                {{ $adapter->bezeichnung }} ({{ $adapter->nummer }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="tripod" class="block font-semibold mb-1">Stativ</label>
                    <select name="tripod" id="tripod" class="w-full p-2 border border-gray-300 rounded">
                        <option value="">— kein Stativ —</option>
                        @foreach ($tripods as $tripod)
                            <option value="{{ $tripod->id }}" @selected(old('tripod', $config->tripod) == $tripod->id)>
                                {{ $tripod->bezeichnung }} ({{ $tripod->nummer }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="tripod_head" class="block font-semibold mb-1">Stativkopf</label>
                    <select name="tripod_head" id="tripod_head" class="w-full p-2 border border-gray-300 rounded">
                        <option value="">— kein Stativkopf —</option>
                        @foreach ($heads as $head)
                            <option value="{{ $head->id }}" @selected(old('tripod_head', $config->tripod_head) == $head->id)>
                                {{ $head->bezeichnung }} ({{ $head->nummer }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label for="notes" class="block font-semibold mb-1">Notizen</label>
                    <textarea
                        name="notes"
                        id="notes"
                        rows="4"
                        class="w-full p-2 border border-gray-300 rounded"
                    >{{ old('notes', $config->notes) }}</textarea>
                </div>
            </div>

            @if ($errors->any())
                <div class="mt-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('error'))
                <div class="mt-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <div class="mt-6 flex gap-2">
                <button
                    type="submit"
                    class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded"
                >
                    Änderungen speichern
                </button>

                <a
                    href="{{ route('productions.show', $config->production_id) }}"
                    class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded"
                >
                    Abbrechen
                </a>
            </div>
        </form>
    </div>
</x-app-layout>