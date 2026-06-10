<x-app-layout>
<x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Produktionen') }}
        </h2>
    </x-slot>

    <div class="max-w-7xl w-4/5  mx-auto mt-6 bg-white p-6 border border-gray-400 rounded-md shadow-md">
<form action="{{ route('productions.update', $production->id) }}" method="POST">
        @csrf
        @method('PUT')
    <div class="flex flex-wrap gap-4">
    <div class="w-full md:flex-1">
<label for="bezeichnung" class="block text-sm font-medium text-gray-700">Bezeichnung: </label>
<input
type="text"
name="bezeichnung"
id="bezeichnung"
value="{{ $production->bezeichnung }}"><br>
    </div>
    <div class="w-full md:flex-1">
<!-- Eingabefeld für Mietbeginn (booking_start) -->
    <label for="booking_start" class="block text-sm font-medium text-gray-700">Mietbeginn:</label>
    <input type="text" name="booking_start" id="booking_start" class="form-control datepicker" value="{{ \Carbon\Carbon::parse($production->booking_start)->format('d.m.Y') }}"><br>
    </div>
    <div class="w-full md:flex-1">
    <!-- Eingabefeld für Mietende (booking_end) -->
    <label for="booking_end" class="block text-sm font-medium text-gray-700">Mietende:</label>
    <input type="text" name="booking_end" id="booking_end" class="form-control datepicker" value="{{ \Carbon\Carbon::parse($production->booking_end)->format('d.m.Y') }}"><br>
    </div>
<button type="submit" style=" background-color: orange;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    font-weight: bold;
    cursor: pointer;">
    Produktion speichern
</button>
</div>
{{-- <div class="errors"> --}}
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
