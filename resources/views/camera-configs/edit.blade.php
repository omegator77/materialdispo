<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Kamera-Konfiguration bearbeiten"></x-page-header>
    </x-slot>

    <div class="page-narrow"><div class="card card-pad">
        <form action="{{ route('camera-config.update', $config->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <div>
                    <label for="cam_number" class="form-label">Kamera-Nummer</label>
                    <input
                        type="text"
                        name="cam_number"
                        id="cam_number"
                        value="{{ old('cam_number', $config->cam_number) }}"
                        class="form-control w-full p-2"
                        required
                    >
                </div>

                <div>
                    <label for="camera" class="form-label">Kamera</label>
                    <select name="camera" id="camera" class="form-control w-full p-2" required>
                        @foreach ($cameras as $camera)
                            <option value="{{ $camera->id }}" @selected(old('camera', $config->item_id) == $camera->id) @disabled(isset($blocked[$camera->id]))>
                                {{ $camera->bezeichnung }} ({{ $camera->nummer }}) @if(isset($blocked[$camera->id])) — {{ $blocked[$camera->id] }}@endif</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="lens" class="form-label">Objektiv</label>
                    <select name="lens" id="lens" class="form-control w-full p-2">
                        <option value="">— kein Objektiv —</option>
                        @foreach ($lenses as $lens)
                            <option value="{{ $lens->id }}" @selected(old('lens', $config->lens) == $lens->id) @disabled(isset($blocked[$lens->id]))>
                                {{ $lens->bezeichnung }} ({{ $lens->nummer }}) @if(isset($blocked[$lens->id])) — {{ $blocked[$lens->id] }}@endif</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="large_lens_adapter" class="form-label">Large-Lens-Adapter</label>
                    <select name="large_lens_adapter" id="large_lens_adapter" class="form-control w-full p-2">
                        <option value="">— kein Adapter —</option>
                        @foreach ($adapters as $adapter)
                            <option value="{{ $adapter->id }}" @selected(old('large_lens_adapter', $config->large_lens_adapter) == $adapter->id) @disabled(isset($blocked[$adapter->id]))>
                                {{ $adapter->bezeichnung }} ({{ $adapter->nummer }}) @if(isset($blocked[$adapter->id])) — {{ $blocked[$adapter->id] }}@endif</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="tripod" class="form-label">Stativ</label>
                    <select name="tripod" id="tripod" class="form-control w-full p-2">
                        <option value="">— kein Stativ —</option>
                        @foreach ($tripods as $tripod)
                            <option value="{{ $tripod->id }}" @selected(old('tripod', $config->tripod) == $tripod->id) @disabled(isset($blocked[$tripod->id]))>
                                {{ $tripod->bezeichnung }} ({{ $tripod->nummer }}) @if(isset($blocked[$tripod->id])) — {{ $blocked[$tripod->id] }}@endif</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="tripod_head" class="form-label">Stativkopf</label>
                    <select name="tripod_head" id="tripod_head" class="form-control w-full p-2">
                        <option value="">— kein Stativkopf —</option>
                        @foreach ($heads as $head)
                            <option value="{{ $head->id }}" @selected(old('tripod_head', $config->tripod_head) == $head->id) @disabled(isset($blocked[$head->id]))>
                                {{ $head->bezeichnung }} ({{ $head->nummer }}) @if(isset($blocked[$head->id])) — {{ $blocked[$head->id] }}@endif</option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label for="notes" class="form-label">Notizen</label>
                    <textarea
                        name="notes"
                        id="notes"
                        rows="4"
                        class="form-control w-full p-2"
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
                    class="btn btn-primary"
                >
                    Änderungen speichern
                </button>

                <a
                    href="{{ route('productions.show', $config->production_id) }}"
                    class="btn btn-secondary"
                >
                    Abbrechen
                </a>
            </div>
        </form>
    </div></div>
</x-app-layout>