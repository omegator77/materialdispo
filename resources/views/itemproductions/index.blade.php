<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Packliste
            </h2>
        </div>
    </x-slot>

    {{-- Filter --}}
    <div class="max-w-7xl w-11/12 mx-auto mt-6">
        <div class="bg-white border border-gray-300 rounded-lg shadow-md p-4">
            <form method="GET" action="{{ route('itemprods') }}">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="productionFilter" class="block text-sm font-medium text-gray-700 mb-1">
                            Produktion
                        </label>
                        <select id="productionFilter" name="production_id" class="form-control w-full" onchange="this.form.submit()">
                            <option value="">Alle Produktionen</option>
                            @foreach($allProductions as $production)
                                <option value="{{ $production->id }}" {{ ($filters['production_id'] ?? '') == $production->id ? 'selected' : '' }}>
                                    {{ $production->bezeichnung }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="unitFilter" class="block text-sm font-medium text-gray-700 mb-1">
                            Gruppe
                        </label>
                        <select id="unitFilter" name="unit_id" class="form-control w-full" onchange="this.form.submit()">
                            <option value="">Alle Gruppen</option>
                            @foreach($allUnits as $unit)
                                <option value="{{ $unit->id }}" {{ ($filters['unit_id'] ?? '') == $unit->id ? 'selected' : '' }}>
                                    {{ $unit->bezeichnung }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="itemFilter" class="block text-sm font-medium text-gray-700 mb-1">
                            Gerät
                        </label>
                        <select id="itemFilter" name="item_id" class="form-control w-full" onchange="this.form.submit()">
                            <option value="">Alle Geräte</option>
                            @foreach($allItems as $item)
                                <option value="{{ $item->id }}" {{ ($filters['item_id'] ?? '') == $item->id ? 'selected' : '' }}>
                                    {{ $item->bezeichnung }} {{ $item->nummer ?? '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                @if(($filters['production_id'] ?? null) || ($filters['unit_id'] ?? null) || ($filters['item_id'] ?? null))
                    <div class="mt-4 flex justify-end">
                        <a href="{{ route('itemprods') }}"
                           class="inline-flex justify-center bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-4 rounded">
                            Filter zurücksetzen
                        </a>
                    </div>
                @endif
            </form>
        </div>
    </div>

    @php
        $itemproductionsByProduction = $itemproductions->groupBy(fn ($row) => $row->production->id);
        $cameraConfigsByProduction = $cameraConfigs->groupBy(fn ($config) => $config->production->id);

        $productionIds = $itemproductionsByProduction
            ->keys()
            ->merge($cameraConfigsByProduction->keys())
            ->unique();

        $productionsById = $allProductions->keyBy('id');

       $itemLabel = function ($item) {
    if (! $item) {
        return '—';
    }

    return $item->nummer
        ? $item->bezeichnung . ' (' . $item->nummer . ')'
        : $item->bezeichnung;

};
    @endphp

    <div class="max-w-7xl w-11/12 mx-auto mt-6 space-y-6">
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
                <summary class="cursor-pointer bg-gray-100 hover:bg-gray-200 px-4 py-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div>
                            <div class="font-bold text-lg text-gray-900">
                                {{ $production->bezeichnung ?? 'Unbekannte Produktion' }}
                            </div>

                            <div class="text-sm text-gray-500">
                                {{ $productionConfigs->count() }} Kamerazüge ·
                                {{ $productionItems->count() }} Einzelgerät(e)
                            </div>
                        </div>

                        @if($production)
                            <div class="text-sm text-gray-600">
                                {{ $production->booking_start ? \Carbon\Carbon::parse($production->booking_start)->format('d.m.Y') : '—' }}
                                –
                                {{ $production->booking_end ? \Carbon\Carbon::parse($production->booking_end)->format('d.m.Y') : '—' }}
                            </div>
                        @endif
                    </div>
                </summary>

                <div class="p-4 md:p-6 space-y-6">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b pb-4">
                        <div class="text-sm text-gray-600">
                            @if($production)
                                Zeitraum:
                                <strong>{{ $production->booking_start ? \Carbon\Carbon::parse($production->booking_start)->format('d.m.Y') : '—' }}</strong>
                                bis
                                <strong>{{ $production->booking_end ? \Carbon\Carbon::parse($production->booking_end)->format('d.m.Y') : '—' }}</strong>
                            @endif
                        </div>

                        @if($production)
                            <a href="{{ route('productions.pdf', $production->id) }}"
                               class="inline-flex justify-center bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded">
                                PDF exportieren
                            </a>
                        @endif
                    </div>

                    {{-- Kamerazüge --}}
                    @if($productionConfigs->count())
                        <section>
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">
                                Kamerazüge
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                                @foreach($productionConfigs as $config)
                                    <div class="border border-gray-300 rounded-lg p-4 bg-gray-50">
                                        <div class="font-bold text-lg text-gray-900 mb-3">
                                            {{ $config->cam_number ?? 'Kamera' }}
                                        </div>

                                        <div class="space-y-2 text-sm text-gray-700">
                                            <div>
                                                <span class="block text-gray-500">Kamera</span>
                                                <span class="font-semibold">{{ $itemLabel($config->item ?? null) }}</span>
                                            </div>

                                            <div>
                                                <span class="block text-gray-500">Objektiv</span>
                                                <span class="font-semibold">{{ $itemLabel($config->lensItem ?? null) }}</span>
                                            </div>

                                            <div>
                                                <span class="block text-gray-500">Adapter</span>
                                                <span class="font-semibold">{{ $itemLabel($config->adapterItem ?? null) }}</span>
                                            </div>

                                            <div>
                                                <span class="block text-gray-500">Stativkopf</span>
                                                <span class="font-semibold">{{ $itemLabel($config->headItem ?? null) }}</span>
                                            </div>

                                            <div>
                                                <span class="block text-gray-500">Stativ</span>
                                                <span class="font-semibold">{{ $itemLabel($config->tripodItem ?? null) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    {{-- Weitere Geräte --}}
                    @if($itemsByUnit->count())
                        <section>
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">
                                Weitere Geräte
                            </h3>

                            <div class="space-y-4">
                                @foreach($itemsByUnit as $unitName => $rows)
                                    <div class="border border-gray-300 rounded-lg overflow-hidden bg-white">
                                        <div class="bg-gray-100 px-4 py-3 font-semibold text-gray-800">
                                            {{ $unitName }}
                                            <span class="font-normal text-sm text-gray-500">
                                                ({{ $rows->count() }})
                                            </span>
                                        </div>

                                        {{-- Desktop --}}
                                        <div class="hidden md:block">
                                            <table class="w-full text-sm">
                                                <tbody>
                                                    @foreach($rows as $row)
                                                        <tr class="border-t hover:bg-gray-50">
                                                            <td class="px-4 py-3 font-medium text-gray-900 w-2/3">
                                                                {{ $itemLabel($row->item ?? null) }}
                                                            </td>

                                                            <td class="px-4 py-3 text-gray-600">
                                                                {{ $row->notes ?? $row->pivot->notes ?? '—' }}
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>

                                        {{-- Handy --}}
                                        <div class="md:hidden divide-y">
                                            @foreach($rows as $row)
                                                <div class="p-4">
                                                    <div class="font-semibold text-gray-900">
                                                        {{ $itemLabel($row->item ?? null) }}
                                                    </div>

                                                    @php
                                                        $note = $row->notes ?? $row->pivot->notes ?? null;
                                                    @endphp

                                                    @if($note)
                                                        <div class="text-sm text-gray-500 mt-1">
                                                            {{ $note }}
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endif
                </div>
            </details>
        @empty
            <div class="bg-white border border-gray-300 rounded-lg shadow-md p-6 text-center text-gray-500">
                Keine Einträge gefunden.
            </div>
        @endforelse
    </div>
</x-app-layout>