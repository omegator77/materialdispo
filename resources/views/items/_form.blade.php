<div class="bg-white p-6 border border-gray-300 rounded-lg shadow-md space-y-6">

    {{-- Fehlermeldungen --}}
    @if ($errors->any())
    <div class="bg-red-50 border border-red-300 text-red-700 px-4 py-3 rounded">
        <ul class="list-disc list-inside text-sm">
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- Stammdaten --}}
    <section>
        <h3 class="text-lg font-semibold text-gray-800 mb-4">
            Stammdaten
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="units_id" class="block text-sm font-medium text-gray-700">Gruppe</label>
                <select name="units_id" id="units_id" class="form-control w-full" required>
                    @foreach($units as $unit)
                    <option value="{{ $unit->id }}"
                        {{ old('units_id', $item->units_id ?? '') == $unit->id ? 'selected' : '' }}>
                        {{ $unit->bezeichnung }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4" x-data="{
                    bezeichnung: @js(old('bezeichnung', $item->bezeichnung ?? '')),
                    types: {
                        @foreach($geraetetypen as $geraetetyp)
                        '{{ $geraetetyp->id }}': @js($geraetetyp->bezeichnung),
                        @endforeach
                    },
                    applyType(id) {
                        if (id && this.types[id]) {
                            this.bezeichnung = this.types[id];
                        }
                    }
                }">
                <div>
                    <label for="geraetetyp_id" class="block text-sm font-medium text-gray-700">Gerätetyp</label>
                    <select name="geraetetyp_id" id="geraetetyp_id" class="form-control w-full" @change="applyType($event.target.value)">
                        <option value="">— Kein Typ —</option>
                        @foreach($geraetetypen->groupBy('units_id') as $groupUnitsId => $typesInUnit)
                        <optgroup label="{{ $typesInUnit->first()->unit->bezeichnung ?? 'Ohne Gruppe' }}">
                            @foreach($typesInUnit as $geraetetyp)
                            <option value="{{ $geraetetyp->id }}"
                                {{ old('geraetetyp_id', $item->geraetetyp_id ?? '') == $geraetetyp->id ? 'selected' : '' }}>
                                {{ $geraetetyp->bezeichnung }}
                            </option>
                            @endforeach
                        </optgroup>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-400 mt-1">
                        Füllt die Bezeichnung automatisch aus.
                        <a href="{{ route('geraetetypen.create') }}" target="_blank" class="text-blue-600 hover:underline">Neuen Typ anlegen</a>
                    </p>
                </div>

                <div>
                    <label for="bezeichnung" class="block text-sm font-medium text-gray-700">Bezeichnung</label>
                    <input
                        type="text"
                        name="bezeichnung"
                        id="bezeichnung"
                        x-model="bezeichnung"
                        class="form-control w-full"
                        required>
                </div>
            </div>

            <div>
                <label for="nummer" class="block text-sm font-medium text-gray-700">Nummer</label>
                <input
                    type="text"
                    name="nummer"
                    id="nummer"
                    value="{{ old('nummer', $item->nummer ?? '') }}"
                    class="form-control w-full">
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700">Bemerkung</label>
                <textarea
                    name="description"
                    id="description"
                    rows="3"
                    class="form-control w-full">{{ old('description', $item->description ?? '') }}</textarea>
            </div>
        </div>
    </section>

    {{-- Mietmaterial --}}
    <section class="border-t pt-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">
            Mietmaterial
        </h3>

        @if($item->mietvorgang_manual ?? false)
        <div class="mb-4 bg-blue-50 border border-blue-200 rounded-lg px-4 py-3 text-sm text-blue-800 flex flex-col sm:flex-row sm:items-center gap-2">
            <span>
                Dieses Gerät ist manuell einem
                <a href="{{ route('mietvorgaenge.show', $item->mietvorgang_id) }}" class="underline hover:text-blue-900" target="_blank">Mietvorgang</a>
                zugeordnet — Vermieter und Zeitraum werden dort verwaltet.
            </span>
            <form action="{{ route('items.resetMietvorgang', $item->id) }}" method="POST" class="sm:ml-auto">
                @csrf
                <button type="submit" class="text-blue-700 hover:underline font-medium whitespace-nowrap">
                    Zurücksetzen
                </button>
            </form>
        </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="suppliers_id" class="block text-sm font-medium text-gray-700">Vermieter</label>
                <select name="suppliers_id" id="suppliers_id" class="form-control w-full" @disabled($item->mietvorgang_manual ?? false)>
                    <option value="">Eigentum / kein Vermieter</option>
                    @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->id }}"
                        {{ old('suppliers_id', $item->suppliers_id ?? '') == $supplier->id ? 'selected' : '' }}>
                        {{ $supplier->bezeichnung }}
                    </option>
                    @endforeach
                </select>
                @if($item->mietvorgang_manual ?? false)
                <input type="hidden" name="suppliers_id" value="{{ $item->suppliers_id }}">
                @endif
            </div>

            <div id="rental-fields" class="md:col-span-2">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="rent_start" class="block text-sm font-medium text-gray-700">Mietbeginn</label>
                        <input
                            type="text"
                            name="rent_start"
                            id="rent_start"
                            value="{{ old('rent_start', $item->rent_start ?? '') }}"
                            class="form-control datepicker w-full"
                            placeholder="TT.MM.JJJJ"
                            @readonly($item->mietvorgang_manual ?? false)>
                    </div>

                    <div>
                        <label for="rent_end" class="block text-sm font-medium text-gray-700">Mietende</label>
                        <input
                            type="text"
                            name="rent_end"
                            id="rent_end"
                            value="{{ old('rent_end', $item->rent_end ?? '') }}"
                            class="form-control datepicker w-full"
                            placeholder="TT.MM.JJJJ"
                            @readonly($item->mietvorgang_manual ?? false)>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if((int) ($item->units_id ?? 0) === 1)
    <section class="border-t pt-6 mt-6">
        <h4 class="text-lg font-semibold text-gray-800 mb-4">
            Kamera-Details
        </h4>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Body Seriennummer</label>
                <input type="text" name="body_serial"
                    value="{{ old('body_serial', $item->cameraDetail->body_serial ?? '') }}"
                    class="mt-1 block w-full rounded border-gray-300">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Fiber Adapter Seriennummer</label>
                <input type="text" name="fiber_adapter_serial"
                    value="{{ old('fiber_adapter_serial', $item->cameraDetail->fiber_adapter_serial ?? '') }}"
                    class="mt-1 block w-full rounded border-gray-300">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Großer Sucher Modell</label>
                <input type="text" name="large_viewfinder_model"
                    value="{{ old('large_viewfinder_model', $item->cameraDetail->large_viewfinder_model ?? '') }}"
                    class="mt-1 block w-full rounded border-gray-300">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Großer Sucher Seriennummer</label>
                <input type="text" name="large_viewfinder_serial"
                    value="{{ old('large_viewfinder_serial', $item->cameraDetail->large_viewfinder_serial ?? '') }}"
                    class="mt-1 block w-full rounded border-gray-300">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Kleiner Sucher Modell</label>
                <input type="text" name="small_viewfinder_model"
                    value="{{ old('small_viewfinder_model', $item->cameraDetail->small_viewfinder_model ?? '') }}"
                    class="mt-1 block w-full rounded border-gray-300">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Kleiner Sucher Seriennummer</label>
                <input type="text" name="small_viewfinder_serial"
                    value="{{ old('small_viewfinder_serial', $item->cameraDetail->small_viewfinder_serial ?? '') }}"
                    class="mt-1 block w-full rounded border-gray-300">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Großer Sucher Typ</label>
                <select name="large_viewfinder_type"
                    class="mt-1 block w-full rounded border-gray-300">
                    <option value="">—</option>
                    <option value="OLED" @selected(old('large_viewfinder_type', $item->cameraDetail->large_viewfinder_type ?? '') === 'OLED')>OLED</option>
                    <option value="LCD" @selected(old('large_viewfinder_type', $item->cameraDetail->large_viewfinder_type ?? '') === 'LCD')>LCD</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Kleiner Sucher Typ</label>
                <select name="small_viewfinder_type"
                    class="mt-1 block w-full rounded border-gray-300">
                    <option value="">—</option>
                    <option value="OLED" @selected(old('small_viewfinder_type', $item->cameraDetail->small_viewfinder_type ?? '') === 'OLED')>OLED</option>
                    <option value="LCD" @selected(old('small_viewfinder_type', $item->cameraDetail->small_viewfinder_type ?? '') === 'LCD')>LCD</option>
                </select>
            </div>

            <div class="md:col-span-2">
                <label class="flex items-center gap-2">
                    <input type="checkbox"
                        name="ssl_license"
                        value="1"
                        @checked(old('ssl_license', $item->cameraDetail->ssl_license ?? false))>
                    <span class="text-sm font-medium text-gray-700">SSL Lizenz vorhanden</span>
                </label>
            </div>
        </div>
    </section>
    @endif

    @if((int) ($item->units_id ?? 0) === 2)
<section class="border-t pt-6 mt-6">
    <h4 class="text-lg font-semibold text-gray-800 mb-4">
        Objektiv-Details
    </h4>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

        <div>
            <label class="block text-sm font-medium text-gray-700">Hersteller</label>
            <input type="text"
                name="lens_manufacturer"
                value="{{ old('lens_manufacturer', $item->lensDetail->manufacturer ?? '') }}"
                class="mt-1 block w-full rounded border-gray-300">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Modell</label>
            <input type="text"
                name="lens_model"
                value="{{ old('lens_model', $item->lensDetail->model ?? '') }}"
                class="mt-1 block w-full rounded border-gray-300">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Seriennummer</label>
            <input type="text"
                name="lens_serial_number"
                value="{{ old('lens_serial_number', $item->lensDetail->serial_number ?? '') }}"
                class="mt-1 block w-full rounded border-gray-300">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Zoomfaktor</label>
            <input type="text"
                name="lens_zoom_factor"
                value="{{ old('lens_zoom_factor', $item->lensDetail->zoom_factor ?? '') }}"
                class="mt-1 block w-full rounded border-gray-300">
        </div>

    </div>

    <h5 class="mt-6 font-medium text-gray-700">
        Zoomgriff
    </h5>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">

        <div>
            <label class="block text-sm font-medium text-gray-700">Typ</label>
            <input type="text"
                name="lens_zoom_servo_model"
                value="{{ old('lens_zoom_servo_model', $item->lensDetail->zoom_servo_model ?? '') }}"
                class="mt-1 block w-full rounded border-gray-300">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Seriennummer</label>
            <input type="text"
                name="lens_zoom_servo_serial_number"
                value="{{ old('lens_zoom_servo_serial_number', $item->lensDetail->zoom_servo_serial_number ?? '') }}"
                class="mt-1 block w-full rounded border-gray-300">
        </div>

    </div>

    <h5 class="mt-6 font-medium text-gray-700">
        Schärfegriff
    </h5>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">

        <div>
            <label class="block text-sm font-medium text-gray-700">Typ</label>
            <input type="text"
                name="lens_focus_servo_model"
                value="{{ old('lens_focus_servo_model', $item->lensDetail->focus_servo_model ?? '') }}"
                class="mt-1 block w-full rounded border-gray-300">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Seriennummer</label>
            <input type="text"
                name="lens_focus_servo_serial_number"
                value="{{ old('lens_focus_servo_serial_number', $item->lensDetail->focus_servo_serial_number ?? '') }}"
                class="mt-1 block w-full rounded border-gray-300">
        </div>

    </div>
</section>
@endif
    
    @if(in_array((int) ($item->units_id ?? 0), [9, 10], true))
    <section class="border-t pt-6 mt-6">
        <h4 class="text-lg font-semibold text-gray-800 mb-4">
            Monitor-Details
        </h4>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Hersteller</label>
                <input type="text" name="manufacturer"
                    value="{{ old('manufacturer', $item->monitorDetail->manufacturer ?? '') }}"
                    class="mt-1 block w-full rounded border-gray-300">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Modell</label>
                <input type="text" name="model"
                    value="{{ old('model', $item->monitorDetail->model ?? '') }}"
                    class="mt-1 block w-full rounded border-gray-300">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Seriennummer</label>
                <input type="text" name="serial_number"
                    value="{{ old('serial_number', $item->monitorDetail->serial_number ?? '') }}"
                    class="mt-1 block w-full rounded border-gray-300">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Bildschirmgröße</label>
                <input type="text" name="screen_size"
                    placeholder='z. B. 24"'
                    value="{{ old('screen_size', $item->monitorDetail->screen_size ?? '') }}"
                    class="mt-1 block w-full rounded border-gray-300">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Max. Eingabeformat</label>
                <input type="text" name="max_input_format"
                    placeholder="z. B. 1080p50 (3G)"
                    value="{{ old('max_input_format', $item->monitorDetail->max_input_format ?? '') }}"
                    class="mt-1 block w-full rounded border-gray-300">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Wandler Nr.</label>
                <input type="text" name="converter_number"
                    value="{{ old('converter_number', $item->monitorDetail->converter_number ?? '') }}"
                    class="mt-1 block w-full rounded border-gray-300">
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Wandler</label>
                <input type="text" name="converter_model"
                    placeholder="z. B. BMD BiDirectional 3G"
                    value="{{ old('converter_model', $item->monitorDetail->converter_model ?? '') }}"
                    class="mt-1 block w-full rounded border-gray-300">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Standfuß Nr.</label>
                <input type="text" name="stand_number"
                    value="{{ old('stand_number', $item->monitorDetail->stand_number ?? '') }}"
                    class="mt-1 block w-full rounded border-gray-300">
            </div>
        </div>

        <div class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-4">
            <label class="flex items-center gap-2">
                <input type="checkbox" name="has_speakers" value="1"
                    @checked(old('has_speakers', $item->monitorDetail->has_speakers ?? false))>
                <span>Lautsprecher</span>
            </label>

            <label class="flex items-center gap-2">
                <input type="checkbox" name="has_headphone" value="1"
                    @checked(old('has_headphone', $item->monitorDetail->has_headphone ?? false))>
                <span>Kopfhörer</span>
            </label>

            <label class="flex items-center gap-2">
                <input type="checkbox" name="converter_audio" value="1"
                    @checked(old('converter_audio', $item->monitorDetail->converter_audio ?? false))>
                <span>Wandler Audio</span>
            </label>

            <label class="flex items-center gap-2">
                <input type="checkbox" name="has_stand" value="1"
                    @checked(old('has_stand', $item->monitorDetail->has_stand ?? false))>
                <span>Standfuß vorhanden</span>
            </label>
        </div>
    </section>
    @endif



    {{-- Aktionen --}}
    <div class="border-t pt-6 flex flex-col sm:flex-row gap-3 sm:justify-end">
        <a href="{{ route('items.index') }}"
            class="inline-flex justify-center bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-4 rounded">
            Abbrechen
        </a>

        <button type="submit"
            class="inline-flex justify-center bg-orange-400 hover:bg-orange-500 text-white font-semibold py-2 px-4 rounded">
            Speichern
        </button>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const supplier = document.getElementById('suppliers_id');
        const rentalFields = document.getElementById('rental-fields');
        const rentStart = document.getElementById('rent_start');
        const rentEnd = document.getElementById('rent_end');

        function toggleRentalFields() {
            if (supplier.value) {
                rentalFields.style.display = 'block';
            } else {
                rentalFields.style.display = 'none';
                rentStart.value = '';
                rentEnd.value = '';
            }
        }

        supplier.addEventListener('change', toggleRentalFields);
        toggleRentalFields();
    });
</script>