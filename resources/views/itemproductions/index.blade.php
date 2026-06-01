<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Packliste') }}
        </h2>
    </x-slot>

    {{-- Filterformular --}}
    <div class="max-w-7xl w-4/5 mx-auto mt-6 bg-white p-6 border border-gray-400 rounded-md shadow-md">
        <form method="GET" action="{{ route('itemprods') }}">
            <div class="flex flex-wrap gap-4">
                <div class="w-full flex-1 text-center">
                    <label for="productionFilter" class="block text-sm font-medium text-gray-700">Produktion:</label>
                    <select class="rounded-md" id="productionFilter" name="production_id" onchange="this.form.submit()">
                        <option value="">Alle Produktionen</option>
                        @foreach($allProductions as $production)
                            <option value="{{ $production->id }}" {{ ($filters['production_id'] ?? '') == $production->id ? 'selected' : '' }}>
                                {{ $production->bezeichnung }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="w-full flex-1 text-center">
                    <label for="unitFilter" class="block text-sm font-medium text-gray-700">Gruppe:</label>
                    <select class="rounded-md" id="unitFilter" name="unit_id" onchange="this.form.submit()">
                        <option value="">Alle Gruppen</option>
                        @foreach($allUnits as $unit)
                            <option value="{{ $unit->id }}" {{ ($filters['unit_id'] ?? '') == $unit->id ? 'selected' : '' }}>
                                {{ $unit->bezeichnung }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="w-full flex-1 text-center">
                    <label for="itemFilter" class="block text-sm font-medium text-gray-700">Gerät:</label>
                    <select class="rounded-md" id="itemFilter" name="item_id" onchange="this.form.submit()">
                        <option value="">Alle Geräte</option>
                        @foreach($allItems as $item)
                            <option value="{{ $item->id }}" {{ ($filters['item_id'] ?? '') == $item->id ? 'selected' : '' }}>
                                {{ $item->bezeichnung }}
                                @isset($item->nummer)
                                    {{ $item->nummer }}
                                @endisset
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>
    </div>

    @php
        $itemproductionsByProduction = $itemproductions->groupBy(fn ($row) => $row->production->id);
        $cameraConfigsByProduction = $cameraConfigs->groupBy(fn ($config) => $config->production->id);

        $productionIds = $itemproductionsByProduction
            ->keys()
            ->merge($cameraConfigsByProduction->keys())
            ->unique();

        $productionsById = $allProductions->keyBy('id');
    @endphp

    <div class="w-4/5 mx-auto mt-6 space-y-6">
        @forelse($productionIds as $productionId)
            @php
                $production = $productionsById->get($productionId);
                $productionItems = $itemproductionsByProduction->get($productionId, collect());
                $productionConfigs = $cameraConfigsByProduction->get($productionId, collect());

                $itemsByUnit = $productionItems->groupBy(function ($row) {
                    return $row->item->unit->bezeichnung ?? 'Ohne Gruppe';
                });
            @endphp

            <details class="bg-white border border-gray-300 rounded-lg shadow-md overflow-hidden" open>
                <summary class="cursor-pointer bg-orange-400 px-4 py-3 font-bold text-gray-900">
                    {{ $production->bezeichnung ?? 'Unbekannte Produktion' }}

                    <span class="font-normal text-sm ml-2">
                        {{ $productionConfigs->count() }} Kamerazug/Kamerazüge,
                        {{ $productionItems->count() }} Einzelgerät(e)
                    </span>
                </summary>

                <div class="p-4 space-y-6">
                    <div class="flex justify-between items-center border-b pb-3">
                        <div class="text-sm text-gray-600">
                            @if($production)
                                Zeitraum:
                                <strong>{{ $production->booking_start }}</strong>
                                bis
                                <strong>{{ $production->booking_end }}</strong>
                            @endif
                        </div>

                        @if($production)
                            <a href="{{ route('productions.pdf', $production->id) }}"
                               class="text-red-600 font-bold hover:underline"
                               title="PDF exportieren">
                                PDF exportieren
                            </a>
                        @endif
                    </div>

                    @if($productionConfigs->count())
                        <section>
                            <h3 class="text-lg font-bold mb-3">Kamerazüge</h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                                @foreach($productionConfigs as $config)
                                    <div class="border border-gray-300 rounded-lg p-4 bg-gray-50">
                                        <div class="font-bold text-lg mb-3">
                                            {{ $config->cam_position ?? 'Kamera' }}
                                        </div>

                                        <div class="space-y-1 text-sm">
                                            <div>
                                                <span class="font-semibold">Kamera:</span>
                                                <strong>{{ $config->item->nummer ?? '' }}</strong>
                                                {{ $config->item->bezeichnung ?? '/' }}
                                            </div>

                                            <div>
                                                <span class="font-semibold">Objektiv:</span>
                                                <strong>{{ $config->lensItem->nummer ?? '' }}</strong>
                                                {{ $config->lensItem->bezeichnung ?? '/' }}
                                            </div>

                                            <div>
                                                <span class="font-semibold">Adapter:</span>
                                                <strong>{{ $config->adapterItem->nummer ?? '' }}</strong>
                                                {{ $config->adapterItem->bezeichnung ?? '/' }}
                                            </div>

                                            <div>
                                                <span class="font-semibold">Stativkopf:</span>
                                                <strong>{{ $config->headItem->nummer ?? '' }}</strong>
                                                {{ $config->headItem->bezeichnung ?? '/' }}
                                            </div>

                                            <div>
                                                <span class="font-semibold">Stativ:</span>
                                                <strong>{{ $config->tripodItem->nummer ?? '' }}</strong>
                                                {{ $config->tripodItem->bezeichnung ?? '/' }}
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    @if($itemsByUnit->count())
                        <section>
                            <h3 class="text-lg font-bold mb-3">Weitere Geräte</h3>

                            <div class="space-y-4">
                                @foreach($itemsByUnit as $unitName => $rows)
                                    <div class="border border-gray-300 rounded-lg overflow-hidden">
                                        <div class="bg-gray-200 px-4 py-2 font-bold">
                                            {{ $unitName }}
                                            <span class="font-normal text-sm">({{ $rows->count() }})</span>
                                        </div>

                                        <table class="w-full bg-white">
                                            <tbody>
                                                @foreach($rows as $row)
                                                    <tr class="border-t even:bg-gray-50">
                                                        <td class="px-4 py-2 w-2/3">
                                                            <strong>{{ $row->item->nummer ?? '' }}</strong>
                                                            {{ $row->item->bezeichnung ?? '/' }}
                                                        </td>
                                                        <td class="px-4 py-2 text-gray-600">
                                                            {{ $row->notes ?? $row->pivot->notes ?? '' }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endif
                </div>
            </details>
        @empty
            <div class="bg-white border border-gray-300 rounded-lg shadow-md p-6 text-center text-gray-600">
                Keine Einträge gefunden.
            </div>
        @endforelse
    </div>
</x-app-layout>