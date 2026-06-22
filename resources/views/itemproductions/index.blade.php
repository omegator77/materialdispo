<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Packliste
            </h2>
        </div>
    </x-slot>

    {{-- Filter --}}
    <div class="mt-4 px-4">
        <form method="GET" action="{{ route('itemprods') }}"
              class="flex flex-wrap justify-center items-center gap-2">
            <select name="production_id" class="form-control text-sm py-1.5 w-auto" onchange="this.form.submit()">
                <option value="">Alle Produktionen</option>
                @foreach($allProductions as $production)
                    <option value="{{ $production->id }}" {{ ($filters['production_id'] ?? '') == $production->id ? 'selected' : '' }}>
                        {{ $production->bezeichnung }}
                    </option>
                @endforeach
            </select>

            <select name="unit_id" class="form-control text-sm py-1.5 w-auto" onchange="this.form.submit()">
                <option value="">Alle Gruppen</option>
                @foreach($allUnits as $unit)
                    <option value="{{ $unit->id }}" {{ ($filters['unit_id'] ?? '') == $unit->id ? 'selected' : '' }}>
                        {{ $unit->bezeichnung }}
                    </option>
                @endforeach
            </select>

            <select name="item_id" class="form-control text-sm py-1.5 w-auto" onchange="this.form.submit()">
                <option value="">Alle Geräte</option>
                @foreach($allItems as $item)
                    <option value="{{ $item->id }}" {{ ($filters['item_id'] ?? '') == $item->id ? 'selected' : '' }}>
                        {{ $item->bezeichnung }} {{ $item->nummer ?? '' }}
                    </option>
                @endforeach
            </select>

            @if(($filters['production_id'] ?? null) || ($filters['unit_id'] ?? null) || ($filters['item_id'] ?? null))
                <a href="{{ route('itemprods') }}"
                   class="text-sm text-gray-400 hover:text-gray-700 hover:underline">
                    ✕ zurücksetzen
                </a>
            @endif
        </form>
    </div>

    @php
    $itemproductionsByProduction = $itemproductions->groupBy(fn ($row) => $row->production->id);
    $cameraConfigsByProduction = $cameraConfigs->groupBy(fn ($config) => $config->production->id);

    $productionsById = $allProductions->keyBy('id');
    $today = \Carbon\Carbon::today();

    $productionIds = $itemproductionsByProduction
        ->keys()
        ->merge($cameraConfigsByProduction->keys())
        ->unique()
        ->sortBy(function ($id) use ($productionsById, $today) {
            $p = $productionsById->get($id);
            $expired = $p && $p->booking_end && \Carbon\Carbon::parse($p->booking_end)->lt($today) ? 1 : 0;
            $start   = $p && $p->booking_start ? $p->booking_start : '9999-12-31';
            return $expired . '_' . $start;
        })
        ->values();

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

        <details class="bg-white border border-gray-300 rounded-lg shadow-md overflow-hidden">
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
                @if($production)
                <div class="flex justify-end border-b pb-4">
                    <a href="{{ route('productions.pdf', $production->id) }}"
                        class="inline-flex justify-center bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded">
                        PDF exportieren
                    </a>
                </div>
                @endif

                @if(!empty($production->packlist_notes))
                <div class="mt-4 mb-6 bg-yellow-50 border-l-4 border-yellow-400 rounded-r-lg p-4">
                    <div class="font-semibold text-yellow-800 mb-2">
                        📋 Packlisten-Notiz
                    </div>

                    <div class="text-gray-700 whitespace-pre-line">
                        {{ $production->packlist_notes }}
                    </div>
                </div>
                @endif

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

                            @php
                            $configRows = [
                            'Kamera' => $config->item ?? null,
                            'Objektiv' => $config->lensItem ?? null,
                            'Adapter' => $config->adapterItem ?? null,
                            'Stativkopf' => $config->headItem ?? null,
                            'Stativ' => $config->tripodItem ?? null,
                            ];
                            @endphp

                            <div class="space-y-2 text-sm text-gray-700">
                                @foreach($configRows as $labelName => $configItem)
                                @if($configItem)
                                <div>
                                    <span class="block text-gray-500">{{ $labelName }}</span>
                                    <span class="font-semibold">{{ $itemLabel($configItem) }}</span>
                                </div>
                                @endif
                                @endforeach

                                @if(!empty($config->notes))
                                <div class="pt-2 border-t">
                                    <span class="block text-gray-500">Notiz</span>
                                    <span class="font-semibold whitespace-pre-line">
                                        {{ $config->notes }}
                                    </span>
                                </div>
                                @endif
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
                                                @if(!empty($row->notes))
                                                <span class="text-sm">
                                                    {{ $row->notes }}
                                                </span>
                                                @else
                                                <span class="text-gray-400">—</span>
                                                @endif
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