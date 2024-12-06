<x-app-layout>
<x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Vermieter') }}
        </h2>
    </x-slot>

<div class="overflow-x-auto  w-4/5 mx-auto mt-4 bg bg-white border-gray-400 border rounded-md shadow-md overflow-hidden">
<table class="border-collapse w-full h-full bg-white">
<thead class="text-left bg-orange-400">
<tr>
<th class="text-left pl-4">Bezeichnung</th>
<th class="text-left pl-4">Kontakt</th>
<th class="text-left pl-4">Telefon</th>
<th class="text-left pl-4">Email</th>

</tr>
</thead>
<tbody>
@foreach ( $suppliers as $supplier )
<tr class="even:bg-orange-200">

<td class="text-left pl-4" >{{$supplier->bezeichnung}}</td>
<td class="text-left pl-4">{{$supplier->kontakt}}</td>
<td class="text-left pl-4">{{$supplier->phone}}</td>
<td class="text-left pl-4">{{$supplier->email}}</td>

</tr>
@endforeach
</tbody>
</table>
</div>
</x-app-layout>
