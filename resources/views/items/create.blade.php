
<x-app-layout>
<x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Einheiten') }}
        </h2>
    </x-slot>

    <div class="max-w-7xl w-4/5  mx-auto mt-6 bg-white p-6 border border-gray-400 rounded-md shadow-md">
    <form action="/items" method="POST">
@csrf

<div class="flex flex-wrap gap-4">
    <div class="w-full md:flex-1">    
        <label for="units_id" class="block text-sm font-medium text-gray-700">Gruppe auswählen:</label>
        <select name="units_id" id="units_id" class="form-select">
        @foreach($units as $unit)
            <option value="{{ $unit->id }}">{{ $unit->bezeichnung }}</option>
        @endforeach
        </select>
    </div>        

    <div class="w-full md:flex-1">
        <label for="bezeichnung" class="block text-sm font-medium text-gray-700">Bezeichnung: </label>
        <input
                type="text"
                name="bezeichnung"
                id="bezeichnung"
                placeholder="Bezeichnung ..."
                value="{{ old('bezeichnung') }}">
    </div>

    <div class="w-full md:flex-1">
        <label for="nummer" class="block text-sm font-medium text-gray-700">Nummer: </label>
        <input
                type="text"
                name="nummer"
                id="nummer"
                placeholder="No ..."
                value="{{ old('nummer') }}">
    </div>

    <div class="w-full md:flex-1">
        <label for="description" class="block text-sm font-medium text-gray-700">Bemerkung: </label>
        <input
                type="text"
                name="description"
                id="description"
                placeholder="Bemerkung ..."
                value="{{ old('description') }}">
    </div>   
</div>



    <div class="w-full md:flex-1">
        <label for="suppliers_id" class="block text-sm font-medium text-gray-700">Vermieter:</label>
        <select name="suppliers_id" id="suppliers_id" class="form-select">
        <option value="" selected>-- Bitte wählen --</option>
        @foreach($suppliers as $supplier)
        <option value="{{ $supplier->id }}">{{ $supplier->bezeichnung }}</option>
        @endforeach
        </select>
    </div>
    
    <div id="rental-fields">
    
    <div class="w-full md:flex-1">
        <label for="rent_start"  class="block text-sm font-medium text-gray-700">Mietbeginn:</label>
        <input type="text" name="rent_start" id="rent_start" class="form-control datepicker" placeholder="TT.MM.JJJJ"><br>
    </div>
    <div class="w-full md:flex-1">

    <label for="rent_end"  class="block text-sm font-medium text-gray-700">Mietende:</label>
    <input type="text" name="rent_end" id="rent_end" class="form-control datepicker" placeholder="TT.MM.JJJJ">   </div>

   
    </div>

     <div class="w-full md:w-auto mt-4 md:mt-0 md:ml-auto">
        <button type="submit" class="bg-orange-400 hover:bg-orange-500 text-white font-thin hover:font-extrabold py-1 px-4 rounded focus:outline-none focus:ring">
            Einheit speichern
        </button>
    </div>

        @if ($errors->any())
    <ul>
        @foreach ($errors->all() as $error)
    <li>{{ $error }}</li>
        @endforeach
    </ul>
        @endif
</div>    
</div>
    <div class="max-w-7xl mx-auto mt-6">
        @include('items._table')
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
</x-app-layout>
