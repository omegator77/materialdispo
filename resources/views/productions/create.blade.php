<x-app-layout>
<x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Einheiten') }}
        </h2>
    </x-slot>
    <div class="max-w-7xl w-4/5  mx-auto mt-6 bg-white p-6 border border-gray-400 rounded-md shadow-md">
    <form action="/productions" method="POST">
    @csrf
    <div class="flex flex-wrap gap-4">
        <div class="w-full md:flex-1">
    <label for="bezeichnung" class="block text-sm font-medium text-gray-700">Bezeichnung: </label>
    <input
        type="text"
        name="bezeichnung"
        id="bezeichnung"
        placeholder="Bezeichnung ..."
        value="{{ old('bezeichnung') }}">
        </div>


<!-- Eingabefeld für Mietbeginn (booking_start) -->
 <div class="w-full md:flex-1">
<label for="booking_start" class="block text-sm font-medium text-gray-700">Mietbeginn:</label>
    <input type="text" name="booking_start" id="booking_start" class="form-control" placeholder="TT.MM.JJJJ"><br>
    </div>
    <div class="w-full md:flex-1">
    <!-- Eingabefeld für Mietende (booking_end) -->
    <label for="booking_end" class="block text-sm font-medium text-gray-700">Mietende:</label>
    <input type="text" name="booking_end" id="booking_end" class="form-control" placeholder="TT.MM.JJJJ"><br>
    </div>
    <div class="text-right">
<button type="submit" class="bg-orange-400 hover:bg-orange-500 text-white font-thin hover:font-extrabold py-1 px-4 rounded focus:outline-none focus:ring">
Speichern</button>
</div>
</div>

@if ($errors->any())
<ul>
@foreach ($errors->all() as $error)
<li>{{ $error }}</li>
@endforeach
</ul>
@endif
</div>
@include('productions._table')

</x-app-layout>
