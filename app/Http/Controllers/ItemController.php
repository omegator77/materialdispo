<?php

namespace App\Http\Controllers;

use App\Http\Requests\ItemRequest;
use App\Models\Geraetetyp;
use App\Models\Item;
use App\Models\Mieter;
use App\Models\Production;
use App\Models\Supplier;
use App\Models\Unit;
use App\Services\ItemAvailabilityService;
use App\Services\ItemDetailSyncService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    public function __construct(
        private ItemDetailSyncService $detailSync,
        private ItemAvailabilityService $availability,
    ) {}

    public function index(Request $request)
    {
        $detailRelations = match ((int) $request->get('unit_id', 0)) {
            1 => ['cameraDetail'],
            2 => ['lensDetail'],
            9, 10 => ['monitorDetail'],
            default => ['monitorDetail'],
        };

        $query = Item::with(array_merge(['unit', 'supplier'], $detailRelations));

        if ($request->filled('unit_id')) {
            $query->where('units_id', $request->unit_id);
        }

        if (
            $request->filled('sort_by') &&
            in_array($request->sort_by, ['bezeichnung', 'nummer', 'units_id', 'rent_start', 'rent_end'])
        ) {
            $secondary = $request->sort_by === 'bezeichnung' ? 'nummer' : 'bezeichnung';

            $query->orderBy($request->sort_by, $request->get('sort_direction', 'asc'))
                ->orderBy($secondary, 'asc');
        } else {
            $query->orderBy('bezeichnung', 'asc')
                ->orderBy('nummer', 'asc');
        }

        $items = $query->get();
        $units = Unit::orderBy('bezeichnung')->get();
        $productions = Production::where('booking_end', '>=', today()->toDateString())
            ->orderBy('booking_start')
            ->get();

        return view('items.index', compact('items', 'units', 'productions'));
    }

    public function create()
    {
        $units = Unit::all();
        $suppliers = Supplier::all();
        $mieter = Mieter::all();
        $geraetetypen = Geraetetyp::orderBy('units_id')->orderBy('bezeichnung')->get();

        $item = new Item;

        // Wird für die eingebundene items._table benötigt.
        // Sonst wirft /items/create: Undefined variable $items.
        $items = Item::with(['unit', 'supplier', 'monitorDetail', 'cameraDetail', 'lensDetail'])
            ->orderBy('units_id', 'asc')
            ->orderBy('nummer', 'asc')
            ->get();

        return view('items.create', compact('units', 'suppliers', 'mieter', 'item', 'items', 'geraetetypen'));
    }

    public function store(ItemRequest $request)
    {
        $rentData = $this->prepareRentData($request);
        $verleihData = $this->prepareVerleihData($request);

        $item = Item::create(array_merge($request->validated(), $rentData, $verleihData));

        $item->syncMietvorgang();
        $item->syncVermietvorgang();

        $this->detailSync->syncMonitorDetails($request, $item);
        $this->detailSync->syncLensDetails($request, $item);

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
            'lensDetail',
        ])->findOrFail($id);

        return view('items.show', compact('item'));
    }

    public function edit(string $id)
    {
        $units = Unit::all();
        $suppliers = Supplier::all();
        $mieter = Mieter::all();
        $geraetetypen = Geraetetyp::orderBy('units_id')->orderBy('bezeichnung')->get();

        $item = Item::with(['cameraDetail', 'monitorDetail', 'lensDetail'])->findOrFail($id);

        $item->rent_start = $item->rent_start
            ? Carbon::parse($item->rent_start)->format('d.m.Y')
            : null;

        $item->rent_end = $item->rent_end
            ? Carbon::parse($item->rent_end)->format('d.m.Y')
            : null;

        $item->verleih_start = $item->verleih_start
            ? Carbon::parse($item->verleih_start)->format('d.m.Y')
            : null;

        $item->verleih_end = $item->verleih_end
            ? Carbon::parse($item->verleih_end)->format('d.m.Y')
            : null;

        return view('items.edit', compact('units', 'suppliers', 'mieter', 'item', 'geraetetypen'));
    }

    public function update(ItemRequest $request, string $id)
    {
        $item = Item::findOrFail($id);

        $rentData = $this->prepareRentData($request);
        $verleihData = $this->prepareVerleihData($request);

        if ($verleihData['mieter_id'] && $verleihData['verleih_start'] && $verleihData['verleih_end']) {
            $check = $this->availability->checkForVerleih($item, $verleihData['verleih_start'], $verleihData['verleih_end']);

            if (! $check['available']) {
                return redirect()->back()->withInput()
                    ->withErrors(['verleih_start' => "Gerät kann nicht vermietet werden: {$check['reason']}"]);
            }
        }

        $item->update(array_merge($request->validated(), $rentData, $verleihData));

        $item->syncMietvorgang();
        $item->syncVermietvorgang();

        $this->detailSync->syncCameraDetails($request, $item);
        $this->detailSync->syncMonitorDetails($request, $item);
        $this->detailSync->syncLensDetails($request, $item);

        return redirect()->route('items.index');
    }

    public function destroy(string $id)
    {
        Item::where('id', $id)->delete();

        return redirect()->route('items.index');
    }

    public function resetMietvorgang(Item $item)
    {
        $item->resetMietvorgangAssignment();

        return redirect()->route('items.edit', $item->id)->with('success', 'Mietvorgang-Zuordnung zurückgesetzt.');
    }

    public function resetVermietvorgang(Item $item)
    {
        $item->resetVermietvorgangAssignment();

        return redirect()->route('items.edit', $item->id)->with('success', 'Vermietvorgang-Zuordnung zurückgesetzt.');
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

    private function prepareVerleihData(Request $request): array
    {
        $mieterId = $request->mieter_id ?: null;

        return [
            'mieter_id' => $mieterId,

            'verleih_start' => $mieterId && $request->verleih_start
                ? Carbon::createFromFormat('d.m.Y', $request->verleih_start)->format('Y-m-d')
                : null,

            'verleih_end' => $mieterId && $request->verleih_end
                ? Carbon::createFromFormat('d.m.Y', $request->verleih_end)->format('Y-m-d')
                : null,
        ];
    }
}
