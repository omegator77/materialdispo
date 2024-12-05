<x-app-layout>
<x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Einheiten') }}
        </h2>
    </x-slot>
    <div class="max-w-7xl w-4/5  mx-auto mt-6 bg-white p-6 border border-gray-400 rounded-md shadow-md">

    <h1 class="font-bold text-2xl mb-4">Material Details</h1>

    <div class="flex flex-wrap md:flex-nowrap gap-8">
        <div class="w-full md:w-1/2">

<p><strong>ID:</strong> {{ $item->id }}</p>
<p><strong>Bezeichnung:</strong> {{ $item->bezeichnung }}</p>
<p><strong>Nummer:</strong> {{$item->nummer }}</p>
<p><strong>Gruppe:</strong> {{ $item->unit->bezeichnung }}</p>
<p><strong>Beschreibung:</strong> {{ $item->description }}</p>
{{-- <p><strong>Angemietet:</strong> {{ $item->is_rented ? 'Ja':'Nein' }}</p> --}}
@if ($item->is_rented)
<p><strong>Vermieter:</strong> {{$item->supplier->bezeichnung ?? 'Eigentum'}}</p>
<p><strong>Mietbeginn:</strong> {{ $item->rent_start }}</p>
<p><strong>Mietende:</strong> {{ $item->rent_end }}</p>    
@endif

        </div>
        <div class="w-full md:w-1/2">
<a href="{{ route('items.edit', $item->id) }}" class="bg-orange-500 hover:bg-orange-600 text-white py-1 px-4 rounded font-bold mt-4">Ändern</a>

<form action="/items/{{$item->id}}"
method="POST">
@csrf
@method("DELETE")
<input type="submit" value="Löschen" class="bg-orange-500 hover:bg-orange-600 text-white py-1 px-4 rounded font-bold mt-4">
</form>


<a href="{{ route('items.index') }}" class="bg-orange-500 hover:bg-orange-600 text-white py-1 px-4 rounded font-bold mt-4">Zurück zur Übersicht</a>
        </div> 
    </div>    
</div>


    </x-app-layout>