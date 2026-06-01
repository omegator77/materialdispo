@php
    // Expected vars:
    // $production  Production
    // $cameras     Collection<Item>
    // $lenses      Collection<Item>
    // $tripods     Collection<Item>
    // $heads       Collection<Item>
    // $adapters    Collection<Item> (large lens adapters)
    // $preselectedCameraId int|null from ?camera_item_id=...
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Kamera-Konfiguration hinzufügen
        </h2>
    </x-slot>

    <div class="max-w-4xl mx-auto mt-8 bg-white p-6 border border-gray-200 rounded-md shadow-sm">
        {{-- Keep the action signature simple: only the production in the URL --}}
        <form method="POST" action="{{ route('camera-config.store', $production) }}">
            @csrf

            <div class="mb-4">
                <label for="cam_number" class="block font-semibold mb-1">Kameranummer *</label>
                <input type="text"
                       name="cam_number" id="cam_number"
                       class="w-full border rounded p-2"
                       value="{{ old('cam_number') }}"
                       required>
                @error('cam_number') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="mb-4">
                <label for="camera" class="block font-semibold mb-1">Basis-Kamera *</label>
                <select name="camera" id="camera" class="w-full border rounded p-2" required>
                    <option value="">– auswählen –</option>
                    @foreach(($cameras ?? collect()) as $it)
                        <option value="{{ $it->id }}"
                                @selected((int)old('camera', $preselectedCameraId ?? 0) === (int)$it->id)>
                            {{ $it->bezeichnung  ?? $it->name ?? 'Item #'.$it->id }}
                            @if(!empty($it->nummer)) ({{ $it->nummer }}) @endif
                        </option>
                    @endforeach
                </select>
                @error('camera') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label for="lens" class="block font-semibold mb-1">Optik</label>
                    <select name="lens" id="lens" class="w-full border rounded p-2">
                        <option value="">– optional –</option>
                        @foreach(($lenses ?? collect()) as $it)
                            <option value="{{ $it->id }}" @selected(old('lens') == $it->id)>
                                {{ $it->bezeichnung ?? $it->name ?? 'Item #'.$it->id }}
                                @if (!empty($it->numer)) (NR. {{ $it->nummer }})
                                    
                                @endif
                            </option>
                        @endforeach
                    </select>
                    @error('lens') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="large_lens_adapter" class="block font-semibold mb-1">Largelens Adapter</label>
                    <select name="large_lens_adapter" id="large_lens_adapter" class="w-full border rounded p-2">
                        <option value="">– optional –</option>
                        @foreach(($adapters ?? collect()) as $it)
                            <option value="{{ $it->id }}" @selected(old('large_lens_adapter') == $it->id)>
                                {{ $it->bezeichnung ?? $it->name ?? 'Item #'.$it->id }}
                                @if (!empty($it->nummer)) ({{ $it->nummer }})
                                @endif
                            </option>
                        @endforeach
                    </select>
                    @error('large_lens_adapter') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="tripod" class="block font-semibold mb-1">Stativ</label>
                    <select name="tripod" id="tripod" class="w-full border rounded p-2">
                        <option value="">– optional –</option>
                        @foreach(($tripods ?? collect()) as $it)
                            <option value="{{ $it->id }}" @selected(old('tripod') == $it->id)>
                                {{ $it->bezeichnung ?? $it->name ?? 'Item #'.$it->id }} NR. {{ $it->nummer }}
                            </option>
                        @endforeach
                    </select>
                    @error('tripod') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="tripod_head" class="block font-semibold mb-1">Stativkopf</label>
                    <select name="tripod_head" id="tripod_head" class="w-full border rounded p-2">
                        <option value="">– optional –</option>
                        @foreach(($heads ?? collect()) as $it)
                            <option value="{{ $it->id }}" @selected(old('tripod_head') == $it->id)>
                                {{ $it->bezeichnung ?? $it->name ?? 'Item #'.$it->id }} NR. {{ $it->nummer }}
                            </option>
                        @endforeach
                    </select>
                    @error('tripod_head') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-4">
                <label for="notes" class="block font-semibold mb-1">Notizen</label>
                <textarea name="notes" id="notes" rows="3" class="w-full border rounded p-2">{{ old('notes') }}</textarea>
                @error('notes') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end gap-2 mt-6">
                <a href="{{ route('productions.show', $production) }}" class="px-4 py-2 border rounded">Abbrechen</a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Speichern</button>
            </div>
        </form>

        @if(!empty($preselectedCameraId))
            <p class="text-xs text-gray-500 mt-4">Vorausgewählte Kamera‑ID: {{ $preselectedCameraId }}</p>
        @endif
    </div>
</x-app-layout>
