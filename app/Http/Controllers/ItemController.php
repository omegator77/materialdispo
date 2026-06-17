<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Unit;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        $query = Item::with(['unit', 'supplier', 'monitorDetail']);

        if ($request->filled('unit_id')) {
            $query->where('units_id', $request->unit_id);
        }

        if (
            $request->filled('sort_by') &&
            in_array($request->sort_by, ['bezeichnung', 'nummer', 'units_id', 'rent_start', 'rent_end'])
        ) {
            $query->orderBy('units_id')
                ->orderBy($request->sort_by, $request->get('sort_direction', 'asc'));
        } else {
            $query->orderBy('units_id', 'asc')
                ->orderBy('nummer', 'asc');
        }

        $items = $query->get();
        $units = Unit::all();

        return view('items.index', compact('items', 'units'));
    }

    public function create()
{
    $units = Unit::all();
    $suppliers = Supplier::all();

    $item = new Item();

    // Wird für die eingebundene items._table benötigt.
    // Sonst wirft /items/create: Undefined variable $items.
    $items = Item::with(['unit', 'supplier', 'monitorDetail'])
        ->orderBy('units_id', 'asc')
        ->orderBy('nummer', 'asc')
        ->get();

    return view('items.create', compact('units', 'suppliers', 'item', 'items'));
}

    public function store(Request $request)
    {
        $validated = $this->validateItem($request);
        $rentData = $this->prepareRentData($request);

        $item = Item::create(array_merge($validated, $rentData));

        $this->syncMonitorDetails($request, $item);

        return redirect()->route('items.index');
    }

    public function show(string $id)
    {
        $item = Item::with([
            'unit',
            'supplier',
            'productions',
            'cameraDetail',
            'monitorDetail',
        ])->findOrFail($id);

        return view('items.show', compact('item'));
    }

    public function edit(string $id)
    {
        $units = Unit::all();
        $suppliers = Supplier::all();

        $item = Item::with(['cameraDetail', 'monitorDetail'])->findOrFail($id);

        $item->rent_start = $item->rent_start
            ? Carbon::parse($item->rent_start)->format('d.m.Y')
            : null;

        $item->rent_end = $item->rent_end
            ? Carbon::parse($item->rent_end)->format('d.m.Y')
            : null;

        return view('items.edit', compact('units', 'suppliers', 'item'));
    }

    public function update(Request $request, string $id)
    {
        $item = Item::findOrFail($id);

        $validated = $this->validateItem($request);
        $rentData = $this->prepareRentData($request);

        $item->update(array_merge($validated, $rentData));

        $this->syncCameraDetails($request, $item);
        $this->syncMonitorDetails($request, $item);

        return redirect()->route('items.index');
    }

    public function destroy(string $id)
    {
        Item::where('id', $id)->delete();

        return redirect()->route('items.index');
    }

    private function validateItem(Request $request): array
    {
        return $request->validate([
            /*
            |--------------------------------------------------------------------------
            | Grunddaten
            |--------------------------------------------------------------------------
            */
            'units_id' => ['required', 'exists:units,id'],
            'suppliers_id' => ['nullable', 'exists:suppliers,id'],
            'bezeichnung' => ['required', 'string', 'max:255'],
            'nummer' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],

            /*
            |--------------------------------------------------------------------------
            | Mietdaten
            |--------------------------------------------------------------------------
            | Ein Item ist Mietmaterial, sobald ein Vermieter gesetzt ist.
            | Ohne Vermieter werden rent_start und rent_end später auf null gesetzt.
            */
            'rent_start' => $request->filled('suppliers_id')
                ? ['required', 'date_format:d.m.Y']
                : ['nullable'],

            'rent_end' => $request->filled('suppliers_id')
                ? ['required', 'date_format:d.m.Y', 'after_or_equal:rent_start']
                : ['nullable'],

            /*
            |--------------------------------------------------------------------------
            | Kamera-Metadaten
            |--------------------------------------------------------------------------
            | Werden nur bei Unit "Kameras" in camera_details gespeichert.
            */
            'body_serial' => ['nullable', 'string', 'max:255'],
            'fiber_adapter_serial' => ['nullable', 'string', 'max:255'],

            'large_viewfinder_model' => ['nullable', 'string', 'max:255'],
            'large_viewfinder_type' => ['nullable', 'in:OLED,LCD'],
            'large_viewfinder_serial' => ['nullable', 'string', 'max:255'],

            'small_viewfinder_model' => ['nullable', 'string', 'max:255'],
            'small_viewfinder_type' => ['nullable', 'in:OLED,LCD'],
            'small_viewfinder_serial' => ['nullable', 'string', 'max:255'],

            'ssl_license' => ['nullable', 'boolean'],

            /*
            |--------------------------------------------------------------------------
            | Monitor-Metadaten
            |--------------------------------------------------------------------------
            | Werden nur bei den beiden Monitor-Units in monitor_details gespeichert.
            */
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'screen_size' => ['nullable', 'string', 'max:50'],

            'has_speakers' => ['nullable', 'boolean'],
            'has_headphone' => ['nullable', 'boolean'],

            'converter_number' => ['nullable', 'string', 'max:50'],
            'converter_model' => ['nullable', 'string', 'max:255'],
            'converter_audio' => ['nullable', 'boolean'],

            'max_input_format' => ['nullable', 'string', 'max:255'],

            'has_stand' => ['nullable', 'boolean'],
            'stand_number' => ['nullable', 'string', 'max:50'],
        ]);
    }

    private function prepareRentData(Request $request): array
    {
        $supplierId = $request->suppliers_id ?: null;

        return [
            'suppliers_id' => $supplierId,

            'rent_start' => $supplierId && $request->rent_start
                ? Carbon::createFromFormat('d.m.Y', $request->rent_start)->format('Y-m-d')
                : null,

            'rent_end' => $supplierId && $request->rent_end
                ? Carbon::createFromFormat('d.m.Y', $request->rent_end)->format('Y-m-d')
                : null,
        ];
    }

    private function syncCameraDetails(Request $request, Item $item): void
    {
        if ((int) $request->units_id === 1) {
            $item->cameraDetail()->updateOrCreate(
                ['item_id' => $item->id],
                [
                    'body_serial' => $request->body_serial,
                    'fiber_adapter_serial' => $request->fiber_adapter_serial,

                    'large_viewfinder_model' => $request->large_viewfinder_model,
                    'large_viewfinder_type' => $request->large_viewfinder_type,
                    'large_viewfinder_serial' => $request->large_viewfinder_serial,

                    'small_viewfinder_model' => $request->small_viewfinder_model,
                    'small_viewfinder_type' => $request->small_viewfinder_type,
                    'small_viewfinder_serial' => $request->small_viewfinder_serial,

                    'ssl_license' => $request->boolean('ssl_license'),
                ]
            );

            return;
        }

        $item->cameraDetail()->delete();
    }

    private function syncMonitorDetails(Request $request, Item $item): void
    {
        if ($this->isMonitorUnit($request->units_id)) {
            $item->monitorDetail()->updateOrCreate(
                ['item_id' => $item->id],
                [
                    'manufacturer' => $request->manufacturer,
                    'model' => $request->model,
                    'serial_number' => $request->serial_number,
                    'screen_size' => $request->screen_size,

                    'has_speakers' => $request->boolean('has_speakers'),
                    'has_headphone' => $request->boolean('has_headphone'),

                    'converter_number' => $request->converter_number,
                    'converter_model' => $request->converter_model,
                    'converter_audio' => $request->boolean('converter_audio'),

                    'max_input_format' => $request->max_input_format,

                    'has_stand' => $request->boolean('has_stand'),
                    'stand_number' => $request->stand_number,
                ]
            );

            return;
        }

        $item->monitorDetail()->delete();
    }

    private function isMonitorUnit(int|string|null $unitId): bool
    {
        if (!$unitId) {
            return false;
        }

        return Unit::where('id', $unitId)
            ->whereIn('bezeichnung', [
                'Monitore bis 24 Zoll',
                'Monitore über 24 Zoll',
            ])
            ->exists();
    }
}
