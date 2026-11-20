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

@if (session('success'))
    <div class="mb-4 rounded bg-green-100 p-4 text-green-800">
        {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div class="mb-4 rounded bg-red-100 p-4 text-red-800">
        {{ session('error') }}
    </div>
@endif

@if ($errors->any())
    <div class="mb-4 rounded bg-red-100 p-4 text-red-800">
        <ul class="list-disc pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
    <x-slot name="header">
        <x-page-header title="Kamera-Konfiguration hinzufügen"></x-page-header>
    </x-slot>

    <div class="page-narrow"><div class="card card-pad">
        {{-- Keep the action signature simple: only the production in the URL --}}
        <form method="POST" action="{{ route('camera-config.store', $production) }}">
            @csrf

            <div class="mb-4">
                <label for="cam_number" class="form-label">Kameranummer / Position *</label>
                <input type="text"
                       name="cam_number" id="cam_number"
                       class="form-control w-full p-2"
                       value="{{ old('cam_number', $suggestedCamNumber ?? '') }}"
                       required>
                @error('cam_number') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="mb-4">
                <label for="camera" class="form-label">Basis-Kamera *</label>
                <select name="camera" id="camera" class="form-control w-full p-2" required>
                    <option value="">– auswählen –</option>
                    @foreach(($cameras ?? collect()) as $it)
                        <option value="{{ $it->id }}" @selected((int)old('camera', $preselectedCameraId ?? 0) === (int)$it->id) @disabled(isset($blocked[$it->id]))>
                            {{ $it->bezeichnung  ?? $it->name ?? 'Item #'.$it->id }}
                            @if(!empty($it->nummer)) NR. {{ $it->nummer }} @endif @if(isset($blocked[$it->id])) — {{ $blocked[$it->id] }}@endif</option>
                    @endforeach
                </select>
                @error('camera') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label for="lens" class="form-label">Optik</label>
                    <select name="lens" id="lens" class="form-control w-full p-2">
                        <option value="">– optional –</option>
                        @foreach(($lenses ?? collect()) as $it)
                            <option value="{{ $it->id }}" @selected(old('lens', $suggested['lens'] ?? null) == $it->id) @disabled(isset($blocked[$it->id]))>
                                {{ $it->bezeichnung ?? $it->name ?? 'Item #'.$it->id }}
                                @if (!empty($it->nummer)) NR. {{ $it->nummer }}
                                    
                                @endif @if(isset($blocked[$it->id])) — {{ $blocked[$it->id] }}@endif</option>
                        @endforeach
                    </select>
                    @error('lens') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="large_lens_adapter" class="form-label">Largelens Adapter</label>
                    <select name="large_lens_adapter" id="large_lens_adapter" class="form-control w-full p-2">
                        <option value="">– optional –</option>
                        @foreach(($adapters ?? collect()) as $it)
                            <option value="{{ $it->id }}" @selected(old('large_lens_adapter', $suggested['large_lens_adapter'] ?? null) == $it->id) @disabled(isset($blocked[$it->id]))>
                                {{ $it->bezeichnung ?? $it->name ?? 'Item #'.$it->id }}
                                @if (!empty($it->nummer)) NR. {{ $it->nummer }}
                                @endif @if(isset($blocked[$it->id])) — {{ $blocked[$it->id] }}@endif</option>
                        @endforeach
                    </select>
                                        @error('large_lens_adapter') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="tripod" class="form-label">Stativ</label>
                    <select name="tripod" id="tripod" class="form-control w-full p-2">
                        <option value="">– optional –</option>
                        @foreach(($tripods ?? collect()) as $it)
                            <option value="{{ $it->id }}" @selected(old('tripod', $suggested['tripod'] ?? null) == $it->id) @disabled(isset($blocked[$it->id]))>
                                {{ $it->bezeichnung ?? $it->name ?? 'Item #'.$it->id }} NR. {{ $it->nummer }} @if(isset($blocked[$it->id])) — {{ $blocked[$it->id] }}@endif</option>
                        @endforeach
                    </select>
                    @error('tripod') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="tripod_head" class="form-label">Stativkopf</label>
                    <select name="tripod_head" id="tripod_head" class="form-control w-full p-2">
                        <option value="">– optional –</option>
                        @foreach(($heads ?? collect()) as $it)
                            <option value="{{ $it->id }}" @selected(old('tripod_head', $suggested['tripod_head'] ?? null) == $it->id) @disabled(isset($blocked[$it->id]))>
                                {{ $it->bezeichnung ?? $it->name ?? 'Item #'.$it->id }} NR. {{ $it->nummer }} @if(isset($blocked[$it->id])) — {{ $blocked[$it->id] }}@endif</option>
                        @endforeach
                    </select>
                    @error('tripod_head') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-4">
                <label for="notes" class="form-label">Notizen</label>
                <textarea name="notes" id="notes" rows="3" class="form-control w-full p-2">{{ old('notes') }}</textarea>
                @error('notes') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end gap-2 mt-6">
                <a href="{{ route('productions.show', $production) }}" class="btn btn-secondary">Abbrechen</a>
                <button type="submit" class="btn btn-primary">Speichern</button>
            </div>
        </form>

        

        @if(!empty($suggestedBasis))
            <p class="text-xs text-green-700 mt-4">Zubehör vorbelegt nach {{ $suggestedBasis }} früheren Zügen mit dieser Kamera — frei änderbar.</p>
        @endif
        @if(!empty($preselectedCameraId))
            <p class="text-xs text-gray-500 mt-4">Vorausgewählte Kamera‑ID: {{ $preselectedCameraId }}</p>
        @endif
    </div></div>

</x-app-layout>
