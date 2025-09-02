<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Kamera-Konfiguration hinzufügen
        </h2>
    </x-slot>

    @if (session('error'))
  <div class="mb-4 rounded border border-red-300 bg-red-50 p-2 text-red-800 whitespace-pre-line">
    {{ session('error') }}
  </div>
@endif
@if (session('success'))
  <div class="mb-4 rounded border border-green-300 bg-green-50 p-2 text-green-800">
    {{ session('success') }}
  </div>
@endif


    <div class="max-w-4xl mx-auto mt-8 bg-white p-6 rounded-md shadow-md">
    >
        <form method="POST" action="{{ route('camera-config.store', [$production->id, $item->id]) }}">
        @csrf

            {{-- Kamera (vorausgewählt, nicht änderbar) --}}
            <div class="mb-4">
                <label class="block font-semibold">Kamera:</label>
                <input type="text"
                    value="{{ $item->bezeichnung }} (ID {{ $item->id }})"
                    class="w-full border rounded p-2 bg-gray-100"
                    readonly>
                <input type="hidden" name="camera_item_id" value="{{ $item->id }}">
            </div>

            <div class="mb-4">
                <label for="cam_number" class="block font-semibold">Kameranummer:</label>
                <input type="text" name="cam_number" id="cam_number" class="w-full border rounded p-2" value="{{ old('cam_number') }}" required>
            </div>

            <div class="mb-4">
                <label for="cam_position" class="block font-semibold">Kameraposition:</label>
                <input type="text" name="cam_position" id="cam_position" class="w-full border rounded p-2" value="{{ old('cam_position') }}">
            </div>

            {{-- Objektiv --}}
            <label for="lens" class="block font-semibold">Optik:</label>
            <select name="lens" id="lens" class="w-full border rounded p-2">
                <option value="">— keines —</option>
                @foreach(($lenses ?? collect()) as $i)
                <option value="{{ $i->id }}" @selected(old('lens')==$i->id)>
                    {{ $i->bezeichnung }}
                </option>
                @endforeach
            </select>

            {{-- Stativ --}}
            <div class="mb-4">
                <label for="tripod" class="block font-semibold">Stativ:</label>
                <select name="tripod" id="tripod" class="w-full border rounded p-2">
                    <option value="">— keines —</option>
                    @foreach(($tripods ?? collect()) as $i)
                    <option value="{{ $i->id }}" @selected(old('tripod')==$i->id)>
                        {{ $i->bezeichnung }}
                    </option>
                    @endforeach
                </select>
                @error('tripod_item_id')
                <div class="text-red-600 text-sm">{{ $message }}</div>

                @enderror
            </div>


            {{-- Stativkopf --}}
            <label for="tripod_head" class="block font-semibold">Stativkopf</label>
            <select name="tripod_head" id="tripod_head" class="w-full border rounded p-2">
                <option value="">— keiner —</option>
                @foreach(($heads ?? collect()) as $i)
                <option value="{{ $i->id }}" @selected(old('tripod_head')==$i->id)>
                    {{ $i->bezeichnung }}
                </option>
                @endforeach
            </select>

            {{-- Large-Lens-Adapter (lladap) --}}
            <div class="mb-4">
                <label for="large_lens_adapter" class="block font-semibold">Large-Lens-Adapter:</label>
                <select name="large_lens_adapter" id="large_lens_adapter" class="w-full border rounded p-2">
                    <option value="">— keiner —</option>
                    @foreach(($lladap ?? collect()) as $i)
                    <option value="{{ $i->id }}" @selected(old('large_lens_adapter')==$i->id)>
                        {{ $i->bezeichnung }}
                    </option>
                    @endforeach
                </select>
                @error('lladap_item_id')
                <div class="text-red-600 text-sm">{{ $message }}</div>
                @enderror
            </div>


            <div class="mb-4">
                <label for="notes" class="block font-semibold">Notizen:</label>
                <textarea name="notes" id="notes" class="w-full border rounded p-2">{{ old('notes') }}</textarea>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="bg-orange-400 hover:bg-orange-500 text-white font-thin hover:font-extrabold py-1 px-4 rounded focus:outline-none focus:ring">
                    Speichern
                </button>
               
  
</form>

                <a href="{{ route('productions.show', $production->id) }}" class="ml-4 bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
                    Abbrechen
                </a>
            </div>
        </form>
    </div>
</x-app-layout>