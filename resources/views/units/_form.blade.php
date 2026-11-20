<div class="card card-pad space-y-6">

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
        <h3 class="card-title mb-4">
            Gruppendaten
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="bezeichnung" class="form-label">Bezeichnung</label>
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
                <label for="description" class="form-label">Beschreibung</label>
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
           class="btn btn-secondary">
            Abbrechen
        </a>

        <button type="submit"
                class="btn btn-primary">
            Speichern
        </button>
    </div>
</div>