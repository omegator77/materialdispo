@php
$isEdit = isset($geraetetyp);
@endphp

<div class="bg-white p-6 border border-gray-300 rounded-lg shadow-md">

    @if ($errors->any())
    <div class="bg-red-50 border border-red-300 text-red-700 px-4 py-3 rounded mb-4">
        <ul class="list-disc list-inside text-sm">
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ $isEdit ? route('geraetetypen.update', $geraetetyp->id) : route('geraetetypen.store') }}" class="space-y-4">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <div>
            <label for="units_id" class="block text-sm font-medium text-gray-700">Gruppe</label>
            <select name="units_id" id="units_id" class="form-control w-full" required>
                <option value="">Bitte wählen</option>
                @foreach($units as $unit)
                <option value="{{ $unit->id }}"
                    {{ old('units_id', $isEdit ? $geraetetyp->units_id : '') == $unit->id ? 'selected' : '' }}>
                    {{ $unit->bezeichnung }}
                </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="bezeichnung" class="block text-sm font-medium text-gray-700">Bezeichnung</label>
            <input type="text" name="bezeichnung" id="bezeichnung" class="form-control w-full"
                   value="{{ old('bezeichnung', $isEdit ? $geraetetyp->bezeichnung : '') }}" required>
        </div>

        <div>
            <label for="description" class="block text-sm font-medium text-gray-700">Bemerkung</label>
            <input type="text" name="description" id="description" class="form-control w-full"
                   value="{{ old('description', $isEdit ? $geraetetyp->description : '') }}">
        </div>

        <div class="flex justify-end gap-2 pt-2">
            <a href="{{ route('geraetetypen.index') }}"
               class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-6 rounded">
                Abbrechen
            </a>
            <button type="submit" class="bg-orange-400 hover:bg-orange-500 text-white font-semibold py-2 px-6 rounded">
                Speichern
            </button>
        </div>
    </form>
</div>
