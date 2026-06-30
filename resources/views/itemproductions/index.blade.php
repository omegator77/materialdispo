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
    $archiveThreshold = $today->copy()->subDays(14);

    $statusFor = function ($id) use ($productionsById, $today, $archiveThreshold) {
        $p = $productionsById->get($id);
        if (! $p || ! $p->booking_start || ! $p->booking_end) {
            return 'kommend';
        }

        $start = \Carbon\Carbon::parse($p->booking_start);
        $end = \Carbon\Carbon::parse($p->booking_end);

        if ($end->lt($archiveThreshold)) {
            return 'archiv';
        }
        if ($end->lt($today)) {
            return 'vergangen';
        }
        if ($start->lte($today) && $end->gte($today)) {
            return 'laufend';
        }
        return 'kommend';
    };

    $statusOrder = ['laufend' => 0, 'kommend' => 1, 'vergangen' => 2, 'archiv' => 3];

    $allIds = $itemproductionsByProduction
        ->keys()
        ->merge($cameraConfigsByProduction->keys())
        ->unique()
        ->values();

    // Status pro Produktion nur einmal berechnen statt bei jedem Sortier-/Filterdurchlauf neu zu parsen
    $statusById = $allIds->mapWithKeys(fn ($id) => [$id => $statusFor($id)]);

    $productionIds = $allIds
        ->reject(fn ($id) => $statusById->get($id) === 'archiv')
        ->sortBy(function ($id) use ($productionsById, $statusById, $statusOrder) {
            $p = $productionsById->get($id);
            $start = $p && $p->booking_start ? $p->booking_start : '9999-12-31';
            return $statusOrder[$statusById->get($id)] . '_' . $start;
        })
        ->values();

    $archivedProductionIds = $allIds
        ->filter(fn ($id) => $statusById->get($id) === 'archiv')
        ->sortByDesc(function ($id) use ($productionsById) {
            $p = $productionsById->get($id);
            return $p && $p->booking_end ? $p->booking_end : '0000-01-01';
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
        $status = $statusById->get($productionId);
        @endphp

        @include('itemproductions._production-card', [
            'production' => $production,
            'productionItems' => $productionItems,
            'productionConfigs' => $productionConfigs,
            'status' => $status,
            'itemLabel' => $itemLabel,
        ])
        @empty
        @if($archivedProductionIds->isEmpty())
        <div class="bg-white border border-gray-300 rounded-lg shadow-md p-6 text-center text-gray-500">
            Keine Einträge gefunden.
        </div>
        @endif
        @endforelse

        @if($archivedProductionIds->isNotEmpty())
        <details class="bg-gray-50 border border-gray-300 rounded-lg shadow-md overflow-hidden">
            <summary class="cursor-pointer bg-gray-200 hover:bg-gray-300 px-4 py-4 font-bold text-gray-700">
                Archiv ({{ $archivedProductionIds->count() }}) – Produktionen, deren Ende mehr als 14 Tage zurückliegt
            </summary>

            <div class="p-4 md:p-6 space-y-6">
                @foreach($archivedProductionIds as $productionId)
                @php
                $production = $productionsById->get($productionId);
                $productionItems = $itemproductionsByProduction->get($productionId, collect());
                $productionConfigs = $cameraConfigsByProduction->get($productionId, collect());
                @endphp

                @include('itemproductions._production-card', [
                    'production' => $production,
                    'productionItems' => $productionItems,
                    'productionConfigs' => $productionConfigs,
                    'status' => 'archiv',
                    'itemLabel' => $itemLabel,
                ])
                @endforeach
            </div>
        </details>
        @endif
    </div>
</x-app-layout>