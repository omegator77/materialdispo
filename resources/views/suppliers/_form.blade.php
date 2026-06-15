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
            Vermieterdaten
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="bezeichnung" class="block text-sm font-medium text-gray-700">Name / Firma</label>
                <input
                    type="text"
                    name="bezeichnung"
                    id="bezeichnung"
                    value="{{ old('bezeichnung', $supplier->bezeichnung ?? '') }}"
                    class="form-control w-full"
                    required
                >
            </div>

            <div>
                <label for="kontakt" class="block text-sm font-medium text-gray-700">Kontaktperson</label>
                <input
                    type="text"
                    name="kontakt"
                    id="kontakt"
                    value="{{ old('kontakt', $supplier->kontakt ?? '') }}"
                    class="form-control w-full"
                >
            </div>

            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700">Telefon</label>
                <input
                    type="text"
                    name="phone"
                    id="phone"
                    value="{{ old('phone', $supplier->phone ?? '') }}"
                    class="form-control w-full"
                >
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">E-Mail</label>
                <input
                    type="email"
                    name="email"
                    id="email"
                    value="{{ old('email', $supplier->email ?? '') }}"
                    class="form-control w-full"
                >
            </div>
        </div>
    </section>

    <div class="border-t pt-6 flex flex-col sm:flex-row gap-3 sm:justify-end">
        <a href="{{ route('suppliers.index') }}"
           class="inline-flex justify-center bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-4 rounded">
            Abbrechen
        </a>

        <button type="submit"
                class="inline-flex justify-center bg-orange-400 hover:bg-orange-500 text-white font-semibold py-2 px-4 rounded">
            Speichern
        </button>
    </div>
</div>