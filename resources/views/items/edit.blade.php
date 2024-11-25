<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initialscale=1.0">
<meta http-equiv="X-UA-Compatible" content="ie=edge">
<title>Vorlage</title>
<link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
<x-app-layout>
<x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Einheiten') }}
        </h2>
    </x-slot>

<div class="form">
    <h1>Edit Item</h1>
    <form action="{{ route('items.update', $item->id) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="form-group">
            <label for="bezeichnung">Bezeichnung</label>
            <input type="text" name="bezeichnung" id="bezeichnung" value="{{ $item->bezeichnung }}" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="nummer">Nummer</label>
            <input type="text" name="nummer" id="nummer" value="{{ $item->nummer }}" class="form-control">
        </div>

        <div class="form-group">
            <label for="description">Beschreibung</label>
            <textarea name="description" id="description" class="form-control">{{ $item->description }}</textarea>
        </div>

        <div class="form-group">
            <label for="units_id">Einheit</label>
            <select name="units_id" id="units_id" class="form-control" required>
                @foreach($units as $unit)
                    <option value="{{ $unit->id }}" {{ $item->units_id == $unit->id ? 'selected' : '' }}>
                        {{ $unit->bezeichnung }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="suppliers_id">Lieferant</label>
            <select name="suppliers_id" id="suppliers_id" class="form-control">
            <option value="" {{ is_null($item->suppliers_id) ? 'selected' : '' }}>-- Bitte wählen --</option>
                @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" {{ $item->suppliers_id == $supplier->id ? 'selected' : '' }}>
                        {{ $supplier->bezeichnung }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="rent_start">Mietbeginn</label>
            <input type="text" name="rent_start" id="rent_start" value="{{ \Carbon\Carbon::parse($item->rent_start)->format('d.m.Y') }}" class="form-control">
        </div>

        <div class="form-group">
            <label for="rent_end">Mietende</label>
            <input type="text" name="rent_end" id="rent_end" value="{{ \Carbon\Carbon::parse($item->rent_end)->format('d.m.Y') }}" class="form-control">
        </div>

        <div class="form-group form-check">
            <input type="checkbox" name="is_rented" id="is_rented" class="form-check-input" {{ $item->is_rented ? 'checked' : '' }}>
            <label for="is_rented" class="form-check-label">Verliehen</label>
        </div>

        <button type="submit" style=" background-color: orange;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    font-weight: bold;
    cursor: pointer;">Speichern</button>

        <a href="{{ route('items.index') }}" style=" background-color: orange;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    font-weight: bold;
    cursor: pointer;">Abbrechen</a>
    </form>
</div>

</body>
</x-app-layout>
</html>