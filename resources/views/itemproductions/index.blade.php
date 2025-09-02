    <x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Packliste') }}
        </h2>
    </x-slot>
       

        <!-- Filterformular -->
        <div class="max-w-7xl w-4/5  mx-auto mt-6 bg-white p-6 border border-gray-400 rounded-md shadow-md">
        <form method="GET" action="{{ route('itemprods') }}">

            <div class="flex flex-wrap gap-4">
            <div class="w-full flex-1 text-center">
                <!-- Filter nach Produktion -->
                <label for="productionFilter" class="block text-sm font-medium text-gray-700">Produktion:</label>
                <select class="rounded-md" id="productionFilter" name="production_id" onchange="this.form.submit()">
                    <option value="">Alle Produktionen</option>
                    @foreach($allProductions as $production)
                        <option value="{{ $production->id }}" 
                            {{ ($filters['production_id'] ?? '') == $production->id ? 'selected' : '' }}>
                            {{ $production->bezeichnung }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="w-full flex-1 text-center">
                <!-- Filter nach Gruppe -->
                <label for="unitFilter" class="block text-sm font-medium text-gray-700">Gruppe:</label>
                <select class="rounded-md"  id="unitFilter" name="unit_id" onchange="this.form.submit()">
                    <option value="">Alle Gruppen</option>
                    @foreach($allUnits as $unit)
                        <option value="{{ $unit->id }}" 
                            {{ ($filters['unit_id'] ?? '') == $unit->id ? 'selected' : '' }}>
                            {{ $unit->bezeichnung }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="w-full flex-1 text-center">
                <!-- Filter nach Gerät -->
                <label for="itemFilter" class="block text-sm font-medium text-gray-700">Gerät:</label>
                <select class="rounded-md" id="itemFilter" name="item_id" onchange="this.form.submit()">
                    <option value="">Alle Geräte</option>
                    @foreach($allItems as $item)
                        <option value="{{ $item->id }}" 
                            {{ ($filters['item_id'] ?? '') == $item->id ? 'selected' : '' }}>
                            {{ $item->bezeichnung }}
                        </option>
                    @endforeach
                </select>
            </div>
            </div>
        </form><form method="GET" action="{{ route('itemprods') }}">
        </div>


        <!-- Gefilterte Ergebnisse -->
    <div class="overflow-x-auto w-4/5 mx-auto mt-4 bg bg-white border-gray-400 border rounded-md shadow-md overflow-hidden">
        <table class="border-collapse w-full h-full table-auto bg-white">
        <thead class="text-left bg-orange-400">
                <tr>
        {{--    <th class="text-left pl-4">Produktions ID</th> --}}
                <th class="text-left w-12 pl-4"></th>
                <th class="text-left pl-4">Produktion</th>
        {{--    <th class="text-left pl-4">Item ID</th>        --}}
                <th class="text-left pl-4">Gerät</th>
                <th class="text-left pl-4">Gerätegruppe</th>
                </tr>
            </thead>
            <tbody>
    <!-- Normale Item-Productions -->
    @foreach ($itemproductions as $itemproduction)
    <tr class="even:bg-orange-200">
        <td class="text-left w-12 pl-4">
            <a href="{{ route('productions.pdf', $itemproduction->production->id) }}" class="btn btn-primary" title="PDF Exportieren">
                <i class="text-left text-red-500 fas fa-file-pdf"></i>
            </a>
        </td>        
        <td class="text-left pl-4">{{ $itemproduction->production->bezeichnung }}</td>            
        <td class="text-left pl-4">{{ $itemproduction->item->bezeichnung ?? '/' }}
            @isset($itemproduction->item->nummer)
                <span class="font-bold">{{ $itemproduction->item->nummer }}</span>
            @endisset
        </td>
        <td class="text-left pl-4">{{ $itemproduction->item->unit->bezeichnung ?? '/' }}</td>
    </tr>
    @endforeach

    <!-- Kamera-Konfigurationen -->
    @foreach ($cameraConfigs as $config)
    <tr class="even:bg-orange-200">
        <td class="text-left w-12 pl-4">
            <a href="{{ route('productions.pdf', $config->production->id) }}" class="btn btn-primary" title="PDF Exportieren">
                <i class="text-left text-red-500 fas fa-file-pdf"></i>
            </a>
        </td>        
        <td class="text-left pl-4">{{ $config->production->bezeichnung }}</td>            
        <td class="text-left pl-4">
            <strong>Kamera:</strong> {{ $config->item->bezeichnung ?? '/' }}
            @isset($config->item->nummer)
                <span class="font-bold">{{ $config->item->nummer }}</span>
            @endisset
            <br>
            <strong>Objektiv:</strong> {{ $config->lensItem?->bezeichnung ?? '/' }} No. {{ $config->lensItem?->nummer ?? '/' }}<br>
            <strong>Adapter:</strong>  {{ $config->adapItem?->bezeichnung ?? '/' }} No. {{ $config->adapItem?->nummer ?? '/' }}<br>
            <strong>Stativ:</strong> {{ $config->tripodItem?->bezeichnung ?? '/' }} No.{{ $config->tripodItem?->nummer ?? '/' }}<br>
            <strong>Stativkopf:</strong> {{ $config->headItem?->bezeichnung ?? '/' }} No. {{ $config->headItem?->nummer ?? '/' }}<br>
            <strong>Kameranummer:</strong> {{ $config->cam_number ?? '/' }}<br>
            <strong>Position:</strong> {{ $config->cam_position ?? '/' }}
            @if(!empty($config->notes))
  <div class="mt-2">
    <strong>Notizen:</strong>
    <div class="whitespace-pre-line">{{ $config->notes }}</div>
    {{-- oder: {!! nl2br(e($config->notes)) !!} --}}
  </div>
@endif
            
        </td>
        <td class="text-left pl-4">{{ $config->item->unit->bezeichnung ?? '/' }}</td>
    </tr>
    @endforeach
</tbody>

        </table>
        
        </div>
    </x-app-layout>
