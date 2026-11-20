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
            Mieterdaten
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="bezeichnung" class="form-label">Name / Firma</label>
                <input
                    type="text"
                    name="bezeichnung"
                    id="bezeichnung"
                    value="{{ old('bezeichnung', $mieter->bezeichnung ?? '') }}"
                    class="form-control w-full"
                    required
                >
            </div>

            <div>
                <label for="kontakt" class="form-label">Kontaktperson</label>
                <input
                    type="text"
                    name="kontakt"
                    id="kontakt"
                    value="{{ old('kontakt', $mieter->kontakt ?? '') }}"
                    class="form-control w-full"
                >
            </div>

            <div>
                <label for="phone" class="form-label">Telefon</label>
                <input
                    type="text"
                    name="phone"
                    id="phone"
                    value="{{ old('phone', $mieter->phone ?? '') }}"
                    class="form-control w-full"
                >
            </div>

            <div>
                <label for="email" class="form-label">E-Mail</label>
                <input
                    type="email"
                    name="email"
                    id="email"
                    value="{{ old('email', $mieter->email ?? '') }}"
                    class="form-control w-full"
                >
            </div>
        </div>
    </section>

    <div class="border-t pt-6 flex flex-col sm:flex-row gap-3 sm:justify-end">
        <a href="{{ route('mieter.index') }}"
           class="btn btn-secondary">
            Abbrechen
        </a>

        <button type="submit"
                class="btn btn-primary">
            Speichern
        </button>
    </div>
</div>
