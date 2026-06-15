<div class="bg-white p-6 border border-gray-300 rounded-lg shadow-md space-y-6">

    @if ($errors->any())
        <div class="bg-red-50 border border-red-300 text-red-700 px-4 py-3 rounded">
            <ul class="list-disc list-inside text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section>
        <h3 class="text-lg font-semibold text-gray-800 mb-4">
            Gruppendaten
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="bezeichnung" class="block text-sm font-medium text-gray-700">Bezeichnung</label>
                <input
                    type="text"
                    name="bezeichnung"
                    id="bezeichnung"
                    value="{{ old('bezeichnung', $unit->bezeichnung ?? '') }}"
                    class="form-control w-full"
                    required
                >
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700">Beschreibung</label>
                <textarea
                    name="description"
                    id="description"
                    rows="3"
                    class="form-control w-full"
                >{{ old('description', $unit->description ?? '') }}</textarea>
            </div>
        </div>
    </section>

    <div class="border-t pt-6 flex flex-col sm:flex-row gap-3 sm:justify-end">
        <a href="{{ route('units.index') }}"
           class="inline-flex justify-center bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-4 rounded">
            Abbrechen
        </a>

        <button type="submit"
                class="inline-flex justify-center bg-orange-400 hover:bg-orange-500 text-white font-semibold py-2 px-4 rounded">
            Speichern
        </button>
    </div>
</div>