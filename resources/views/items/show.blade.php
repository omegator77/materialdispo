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
{{ $item->rent_start ? \Carbon\Carbon::parse($item->rent_start)->format('d.m.Y') : '/' }} bis 
{{ $item->rent_end ? \Carbon\Carbon::parse($item->rent_end)->format('d.m.Y') : '/' }}   
@endif

        </div>
        <div class=" w-full  md:w-1/2 p-4 flex flex-col gap-4 will-change-auto">
<a href="{{ route('items.edit', $item->id) }}" class="bg-orange-500 hover:bg-orange-600 text-white py-1 px-4 rounded font-bold w-fit">Ändern</a>

<form action="/items/{{$item->id}}"
method="POST">
@csrf
@method("DELETE")
<input type="submit" value="Löschen" class="bg-orange-500 hover:bg-orange-600 text-white py-1 px-4 rounded font-bold w-fit">
</form>


<a href="{{ route('items.index') }}" class="bg-orange-500 hover:bg-orange-600 text-white py-1 px-4 rounded font-bold w-fit">Zurück</a>
        </div> 
    </div>    
</div>


    </x-app-layout>