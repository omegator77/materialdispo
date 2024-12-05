
<x-app-layout>
<x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Einheiten') }}
        </h2>
    </x-slot>

<div class="max-w-7xl w-4/5  mx-auto mt-6 bg-white p-6 border border-gray-400 rounded-md shadow-md">
    <form action="{{ route('items.update', $item->id) }}" method="POST">
        @csrf
        @method('PUT')
        
    <div class="flex flex-wrap gap-4">    
    <div class="w-full md:flex-1">    
            <label for="bezeichnung" class="block text-sm font-medium text-gray-700">Bezeichnung</label>
            <input type="text" name="bezeichnung" id="bezeichnung" value="{{ $item->bezeichnung }}" class="form-control" required>
        </div>

        <div class="w-full md:flex-1">    
            <label for="nummer" class="block text-sm font-medium text-gray-700">Nummer</label>
            <input type="text" name="nummer" id="nummer" value="{{ $item->nummer }}" class="form-control">
        </div>

        <div class="w-full md:flex-1">    
            <label for="description" class="block text-sm font-medium text-gray-700">Beschreibung</label>
            <textarea name="description" id="description" class="form-control">{{ $item->description }}</textarea>
        </div>

        <div class="w-full md:flex-1">    
            <label for="units_id" class="block text-sm font-medium text-gray-700">Gruppe</label>
            <select name="units_id" id="units_id" class="form-control" required>
                @foreach($units as $unit)
                    <option value="{{ $unit->id }}" {{ $item->units_id == $unit->id ? 'selected' : '' }}>
                        {{ $unit->bezeichnung }}
                    </option>
                @endforeach
            </select>
        </div>
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

     <div class="flex flex-wrap gap-4">    
        <div class="w-full md:flex-1"> 
            <input type="checkbox" name="is_rented" id="is_rented" class="form-check-input" {{ $item->is_rented ? 'checked' : '' }}>
            <label for="is_rented" class="form-check-label">Angemietet</label>
        </div>

        <div class="w-full md:flex-1"> 
            <label for="rent_start">Mietbeginn</label>
            <input type="text" name="rent_start" id="rent_start" value="{{ $item->is_rented ? \Carbon\Carbon::parse($item->rent_start)->format('d.m.Y') : '' }}" class="form-control">
        </div>

        <div class="w-full md:flex-1"> 
            <label for="rent_end">Mietende</label>
            <input type="text" name="rent_end" id="rent_end" value="{{ $item->is_rented ? \Carbon\Carbon::parse($item->rent_end)->format('d.m.Y') : '' }}" class="form-control">
            <br>
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
</div>
</x-app-layout>