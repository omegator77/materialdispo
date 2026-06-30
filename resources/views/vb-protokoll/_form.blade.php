@php
$isEdit = isset($vbProtokoll);
$v = fn ($field, $default = '') => old($field, $isEdit ? ($vbProtokoll->{$field} ?? $default) : $default);

$initialAnforderungen = $isEdit
    ? $vbProtokoll->anforderungen->map(fn ($a) => [
        'mode' => $a->freitext ? 'frei' : 'typ',
        'unit_id' => $a->unit_id,
        'geraetetyp_id' => $a->geraetetyp_id,
        'freitext' => $a->freitext,
        'anzahl' => $a->anzahl,
        'notiz' => $a->notiz,
    ])->values()
    : collect();

$geraetetypenForJs = $geraetetypen->map(fn ($g) => [
    'id' => $g->id,
    'units_id' => $g->units_id,
    'bezeichnung' => $g->bezeichnung,
])->values();
@endphp

<form method="POST"
      action="{{ $isEdit ? route('vb-protokoll.update', $production->id) : route('vb-protokoll.store', $production->id) }}"
      enctype="multipart/form-data"
      class="space-y-6"
      x-data="vbProtokollForm({
          anforderungen: {{ $initialAnforderungen->toJson() }},
          geraetetypen: {{ $geraetetypenForJs->toJson() }}
      })">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <div class="bg-blue-50 border border-blue-200 rounded-lg px-4 py-3 text-sm text-blue-800">
        <strong>{{ $production->bezeichnung }}</strong>
        ({{ \Carbon\Carbon::parse($production->booking_start)->format('d.m.Y') }}
        – {{ \Carbon\Carbon::parse($production->booking_end)->format('d.m.Y') }})
    </div>

    @if($errors->any())
        <div class="bg-red-50 border border-red-300 text-red-700 px-4 py-3 rounded">
            <ul class="list-disc list-inside text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Kopf --}}
    <div class="bg-white border border-gray-300 rounded-lg shadow-md p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Allgemein</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="kunde" class="block text-sm font-medium text-gray-700 mb-1">Kunde</label>
                <input type="text" name="kunde" id="kunde" class="form-control w-full" value="{{ $v('kunde') }}">
            </div>
            <div>
                <label for="produktionsort" class="block text-sm font-medium text-gray-700 mb-1">Produktionsort</label>
                <input type="text" name="produktionsort" id="produktionsort" class="form-control w-full" value="{{ $v('produktionsort') }}">
            </div>
        </div>
    </div>

    {{-- Crew --}}
    <div class="bg-white border border-gray-300 rounded-lg shadow-md p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Crew</h2>

        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
            @php
            $crewFields = [
                'crew_ul' => 'ÜL',
                'crew_bt_sng' => 'BT/SNG',
                'crew_ti' => 'TI',
                'crew_sng' => 'SNG',
                'crew_bt_dl' => 'BT DL',
                'crew_tt' => 'TT',
                'crew_tl' => 'TL',
                'crew_ba' => 'BA',
                'crew_ta' => 'TA',
                'crew_kabelhilfen' => 'Kabelhilfen',
                'crew_kamera' => 'Kamera',
                'crew_evs' => 'EVS',
            ];
            @endphp

            @foreach($crewFields as $field => $label)
            <div>
                <label for="{{ $field }}" class="block text-xs font-medium text-gray-600 mb-1">{{ $label }}</label>
                <input type="text" name="{{ $field }}" id="{{ $field }}" class="form-control w-full text-sm" value="{{ $v($field) }}">
            </div>
            @endforeach
        </div>
    </div>

    {{-- Anforderungen (Soll-Mengen für den Packliste-Abgleich) --}}
    <div class="bg-white border border-gray-300 rounded-lg shadow-md p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-1">Anforderungen</h2>
        <p class="text-xs text-gray-500 mb-4">
            Wird beim Packen mit der tatsächlich gepackten Menge je Gerätekategorie abgeglichen.
        </p>

        <div class="space-y-3">
            <template x-for="(anforderung, index) in anforderungen" :key="index">
                <div class="border border-gray-200 rounded-lg p-3 space-y-2">
                    <div class="flex items-center gap-4 text-xs text-gray-600">
                        <label class="flex items-center gap-1">
                            <input type="radio" :name="`anforderungen[${index}][mode]`" value="typ" x-model="anforderung.mode">
                            Gerätetyp
                        </label>
                        <label class="flex items-center gap-1">
                            <input type="radio" :name="`anforderungen[${index}][mode]`" value="frei" x-model="anforderung.mode">
                            Freitext
                        </label>
                        <button type="button" @click="removeAnforderung(index)" class="ml-auto text-red-500 hover:text-red-700 px-2" title="Entfernen">×</button>
                    </div>

                    <div x-show="anforderung.mode === 'typ'" class="flex flex-col sm:flex-row gap-2">
                        <select :name="`anforderungen[${index}][unit_id]`" x-model="anforderung.unit_id"
                                @change="anforderung.geraetetyp_id = ''" class="form-control sm:w-44 text-sm">
                            <option value="">Gruppe wählen</option>
                            @foreach($units as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->bezeichnung }}</option>
                            @endforeach
                        </select>
                        <select :name="`anforderungen[${index}][geraetetyp_id]`" x-model="anforderung.geraetetyp_id"
                                :disabled="!anforderung.unit_id" class="form-control sm:w-56 text-sm">
                            <option value="">Alle Typen dieser Gruppe</option>
                            <template x-for="typ in typesForUnit(anforderung.unit_id)" :key="typ.id">
                                <option :value="typ.id" x-text="typ.bezeichnung"></option>
                            </template>
                        </select>
                        <input type="number" min="1" :name="`anforderungen[${index}][anzahl]`" x-model="anforderung.anzahl"
                               placeholder="Anzahl" class="form-control w-full sm:w-24 text-sm">
                    </div>

                    <div x-show="anforderung.mode === 'frei'" class="flex flex-col sm:flex-row gap-2">
                        <input type="text" :name="`anforderungen[${index}][freitext]`" x-model="anforderung.freitext"
                               placeholder="z. B. Sandsäcke" class="form-control flex-1 text-sm">
                        <input type="number" min="1" :name="`anforderungen[${index}][anzahl]`" x-model="anforderung.anzahl"
                               placeholder="Anzahl (optional)" class="form-control w-full sm:w-24 text-sm">
                    </div>

                    <input type="text" :name="`anforderungen[${index}][notiz]`" x-model="anforderung.notiz"
                           placeholder="Notiz (optional)" class="form-control w-full text-sm">
                </div>
            </template>
        </div>

        <button type="button" @click="addAnforderung()" class="mt-3 text-sm text-blue-600 hover:underline">
            + Anforderung hinzufügen
        </button>
    </div>

    {{-- Besonderheiten / Kabelwege --}}
    <div class="bg-white border border-gray-300 rounded-lg shadow-md p-6 space-y-4">
        <div>
            <label for="besonderheiten" class="block text-sm font-medium text-gray-700 mb-1">Besonderheiten</label>
            <textarea name="besonderheiten" id="besonderheiten" rows="3" class="form-control w-full">{{ $v('besonderheiten') }}</textarea>
        </div>
        <div>
            <label for="kabelwege" class="block text-sm font-medium text-gray-700 mb-1">Kabelwege, Länge, Überbauten, Besonderheiten</label>
            <textarea name="kabelwege" id="kabelwege" rows="3" class="form-control w-full">{{ $v('kabelwege') }}</textarea>
        </div>
    </div>

    {{-- Audio --}}
    <div class="bg-white border border-gray-300 rounded-lg shadow-md p-6 space-y-4">
        <h2 class="text-lg font-semibold text-gray-800">Audio</h2>

        <div>
            <label for="audio_mic" class="block text-sm font-medium text-gray-700 mb-1">Mic Anzahl und Art</label>
            <textarea name="audio_mic" id="audio_mic" rows="2" class="form-control w-full">{{ $v('audio_mic') }}</textarea>
        </div>
        <div>
            <label for="audio_inear" class="block text-sm font-medium text-gray-700 mb-1">In Ear Sender/Empfänger</label>
            <textarea name="audio_inear" id="audio_inear" rows="2" class="form-control w-full">{{ $v('audio_inear') }}</textarea>
        </div>
        <div>
            <label for="audio_kommplatz" class="block text-sm font-medium text-gray-700 mb-1">Kommplatz/Sprechstellen/4-Draht</label>
            <textarea name="audio_kommplatz" id="audio_kommplatz" rows="2" class="form-control w-full">{{ $v('audio_kommplatz') }}</textarea>
        </div>
    </div>

    {{-- Technik --}}
    <div class="bg-white border border-gray-300 rounded-lg shadow-md p-6 space-y-4">
        <div>
            <label for="isdn_funk" class="block text-sm font-medium text-gray-700 mb-1">ISDN/SIP/Funk</label>
            <textarea name="isdn_funk" id="isdn_funk" rows="2" class="form-control w-full">{{ $v('isdn_funk') }}</textarea>
        </div>
        <div>
            <label for="maz_evs_usb" class="block text-sm font-medium text-gray-700 mb-1">MAZ/EVS/USB</label>
            <textarea name="maz_evs_usb" id="maz_evs_usb" rows="2" class="form-control w-full">{{ $v('maz_evs_usb') }}</textarea>
        </div>
        <div>
            <label for="monitore" class="block text-sm font-medium text-gray-700 mb-1">Monitore (Anzahl, Größe, VKS?, Grafik?)</label>
            <textarea name="monitore" id="monitore" rows="2" class="form-control w-full">{{ $v('monitore') }}</textarea>
        </div>
        <div>
            <label for="sonstiges" class="block text-sm font-medium text-gray-700 mb-1">Sonstiges (Hotel, Zusätze etc.)</label>
            <textarea name="sonstiges" id="sonstiges" rows="2" class="form-control w-full">{{ $v('sonstiges') }}</textarea>
        </div>
        <div>
            <label for="zeitplan" class="block text-sm font-medium text-gray-700 mb-1">Zeitplan</label>
            <textarea name="zeitplan" id="zeitplan" rows="3" class="form-control w-full">{{ $v('zeitplan') }}</textarea>
        </div>
    </div>

    {{-- Fotos --}}
    <div class="bg-white border border-gray-300 rounded-lg shadow-md p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Fotos / Lagepläne</h2>

        @if($isEdit && $vbProtokoll->fotos->count())
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
            @foreach($vbProtokoll->fotos as $foto)
            <div class="relative group">
                <a href="{{ $foto->url() }}" target="_blank">
                    <img src="{{ $foto->url() }}" alt="{{ $foto->original_name }}" class="w-full h-24 object-cover rounded border border-gray-200">
                </a>
                <form method="POST" action="{{ route('vb-protokoll.foto.destroy', $foto->id) }}"
                      onsubmit="return confirm('Foto wirklich löschen?');"
                      class="absolute top-1 right-1">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white text-xs w-5 h-5 rounded-full leading-none">×</button>
                </form>
            </div>
            @endforeach
        </div>
        @endif

        <input type="file" name="fotos[]" multiple accept="image/*" class="form-control w-full text-sm">
        <p class="text-xs text-gray-400 mt-1">JPG/PNG/WEBP, max. 8 MB pro Bild.</p>
    </div>

    <div class="flex justify-end gap-2">
        <a href="{{ $isEdit ? route('vb-protokoll.show', $production->id) : route('productions.show', $production->id) }}"
           class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-6 rounded">
            Abbrechen
        </a>
        <button type="submit" class="bg-orange-400 hover:bg-orange-500 text-white font-semibold py-2 px-6 rounded">
            Speichern
        </button>
    </div>
</form>

<script>
function vbProtokollForm({ anforderungen, geraetetypen }) {
    return {
        anforderungen: anforderungen,
        geraetetypen: geraetetypen,
        typesForUnit(unitId) {
            if (!unitId) {
                return [];
            }
            return this.geraetetypen.filter(t => String(t.units_id) === String(unitId));
        },
        addAnforderung() {
            this.anforderungen.push({ mode: 'typ', unit_id: '', geraetetyp_id: '', freitext: '', anzahl: '', notiz: '' });
        },
        removeAnforderung(index) {
            this.anforderungen.splice(index, 1);
        }
    };
}
</script>
