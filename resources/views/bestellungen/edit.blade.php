<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Bestellung bearbeiten"></x-page-header>
    </x-slot>

    <div class="page-narrow">
        <form action="{{ route('bestellungen.update', $bestellung) }}" method="POST">
            @csrf
            @method('PUT')

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
                    <h3 class="card-title mb-4">Bestellung</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="bestellt_bei" class="form-label">Bestellt bei</label>
                            <input type="text" name="bestellt_bei" id="bestellt_bei" maxlength="255" required
                                   value="{{ old('bestellt_bei', $bestellung->bestellt_bei) }}" class="form-control w-full">
                        </div>
                        <div class="md:col-span-2">
                            <label for="bestellt_was" class="form-label">Bestellt was</label>
                            <textarea name="bestellt_was" id="bestellt_was" rows="4" maxlength="2000" required class="form-control w-full">{{ old('bestellt_was', $bestellung->bestellt_was) }}</textarea>
                        </div>
                        <div>
                            <label for="bezahlt_mit" class="form-label">Bezahlt mit</label>
                            <input type="text" name="bezahlt_mit" id="bezahlt_mit" maxlength="255" required
                                   value="{{ old('bezahlt_mit', $bestellung->bezahlt_mit) }}" class="form-control w-full">
                        </div>
                    </div>
                </section>

                <section>
                    <h3 class="card-title mb-4">Sendung</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="versanddienstleister" class="form-label">Versanddienstleister</label>
                            <select name="versanddienstleister" id="versanddienstleister" class="form-control w-full">
                                <option value="">—</option>
                                @foreach(\App\Models\Bestellung::CARRIERS as $key => $label)
                                    <option value="{{ $key }}" @selected(old('versanddienstleister', $bestellung->versanddienstleister) === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="sendungsnummer" class="form-label">Sendungsnummer</label>
                            <input type="text" name="sendungsnummer" id="sendungsnummer" maxlength="100"
                                   value="{{ old('sendungsnummer', $bestellung->sendungsnummer) }}" class="form-control w-full">
                        </div>
                        @if($bestellung->istAngekommen())
                            <div class="md:col-span-2">
                                <label for="angekommen_notiz" class="form-label">Ankunftsnotiz</label>
                                <input type="text" name="angekommen_notiz" id="angekommen_notiz" maxlength="255"
                                       value="{{ old('angekommen_notiz', $bestellung->angekommen_notiz) }}" class="form-control w-full">
                            </div>
                        @endif
                    </div>
                </section>

                <div class="border-t pt-6 flex flex-col sm:flex-row gap-3 sm:justify-end">
                    <a href="{{ route('bestellungen.show', $bestellung) }}" class="btn btn-secondary">Abbrechen</a>
                    <button type="submit" class="btn btn-primary">Speichern</button>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
