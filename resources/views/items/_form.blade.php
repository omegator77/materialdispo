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

            <div>
                <label for="bezeichnung" class="block text-sm font-medium text-gray-700">Bezeichnung</label>
                <input
                    type="text"
                    name="bezeichnung"
                    id="bezeichnung"
                    value="{{ old('bezeichnung', $item->bezeichnung ?? '') }}"
                    class="form-control w-full"
                    required
                >
            </div>

            <div>
                <label for="nummer" class="block text-sm font-medium text-gray-700">Nummer</label>
                <input
                    type="text"
                    name="nummer"
                    id="nummer"
                    value="{{ old('nummer', $item->nummer ?? '') }}"
                    class="form-control w-full"
                >
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700">Bemerkung</label>
                <textarea
                    name="description"
                    id="description"
                    rows="3"
                    class="form-control w-full"
                >{{ old('description', $item->description ?? '') }}</textarea>
            </div>
        </div>
    </section>

    {{-- Mietmaterial --}}
    <section class="border-t pt-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">
            Mietmaterial
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="suppliers_id" class="block text-sm font-medium text-gray-700">Vermieter</label>
                <select name="suppliers_id" id="suppliers_id" class="form-control w-full">
                    <option value="">Eigentum / kein Vermieter</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}"
                            {{ old('suppliers_id', $item->suppliers_id ?? '') == $supplier->id ? 'selected' : '' }}>
                            {{ $supplier->bezeichnung }}
                        </option>
                    @endforeach
                </select>
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
                        >
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
                        >
                    </div>
                </div>
            </div>
        </div>
    </section>

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
document.addEventListener('DOMContentLoaded', function () {
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