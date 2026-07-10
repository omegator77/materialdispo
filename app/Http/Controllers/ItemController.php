<?php

namespace App\Http\Controllers;

use App\Http\Requests\ItemRequest;
use App\Models\Geraetetyp;
use App\Models\Item;
use App\Models\Mieter;
use App\Models\Production;
use App\Models\Supplier;
use App\Models\Unit;
use App\Services\ItemDetailSyncService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    public function __construct(
        private ItemDetailSyncService $detailSync,
    ) {}

    public function index(Request $request)
    {
        $detailRelations = match ((int) $request->get('unit_id', 0)) {
            1 => ['cameraDetail'],
            2 => ['lensDetail'],
            9, 10 => ['monitorDetail'],
            default => ['monitorDetail'],
        };

        $query = Item::with(array_merge(['unit', 'supplier', 'mietvorgaenge', 'vermietvorgaenge'], $detailRelations));

        if ($request->filled('unit_id')) {
            $query->where('units_id', $request->unit_id);
        }

        if (
            $request->filled('sort_by') &&
            in_array($request->sort_by, ['bezeichnung', 'nummer', 'units_id'])
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

        return view('items.create', compact('units', 'suppliers', 'mieter', 'item', 'geraetetypen'));
    }

    public function store(ItemRequest $request)
    {
        $item = Item::create($request->validated());

        $this->maybeAttachMiete($request, $item);
        $this->maybeAttachVerleih($request, $item);

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
            'mietvorgaenge.supplier',
            'vermietvorgaenge.mieter',
        ])->findOrFail($id);

        return view('items.show', compact('item'));
    }

    public function edit(string $id)
    {
        $units = Unit::all();
        $suppliers = Supplier::all();
        $mieter = Mieter::all();
        $geraetetypen = Geraetetyp::orderBy('units_id')->orderBy('bezeichnung')->get();

        $item = Item::with(['cameraDetail', 'monitorDetail', 'lensDetail', 'mietvorgaenge.supplier', 'vermietvorgaenge.mieter'])->findOrFail($id);

        return view('items.edit', compact('units', 'suppliers', 'mieter', 'item', 'geraetetypen'));
    }

    public function update(ItemRequest $request, string $id)
    {
        $item = Item::findOrFail($id);
        $item->update($request->validated());

        [, $mieteReason] = $this->maybeAttachMiete($request, $item);
        if ($mieteReason) {
            return redirect()->back()->withInput()
                ->withErrors(['rent_start' => "Gerät kann nicht gemietet werden: {$mieteReason}"]);
        }

        [, $verleihReason] = $this->maybeAttachVerleih($request, $item);
        if ($verleihReason) {
            return redirect()->back()->withInput()
                ->withErrors(['verleih_start' => "Gerät kann nicht vermietet werden: {$verleihReason}"]);
        }

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

    /**
     * Trägt der Vermieter+Zeitraum-Block im Item-Formular einen Zeitraum ein,
     * wird eine ZUSÄTZLICHE Mietvorgang-Zuordnung angelegt (findet-oder-legt
     * den passenden Mietvorgang an) — ersetzt keine bestehende Zuordnung.
     *
     * @return array{0: bool, 1: ?string} [wurde zugeordnet, Fehlergrund]
     */
    private function maybeAttachMiete(Request $request, Item $item): array
    {
        if (! $item->suppliers_id || ! $request->filled('rent_start') || ! $request->filled('rent_end')) {
            return [false, null];
        }

        $rentStart = Carbon::createFromFormat('d.m.Y', $request->rent_start)->format('Y-m-d');
        $rentEnd = Carbon::createFromFormat('d.m.Y', $request->rent_end)->format('Y-m-d');

        $result = $item->syncMietvorgang($rentStart, $rentEnd);

        return [$result['added'], ($result['added'] || $result['alreadyAttached']) ? null : $result['reason']];
    }

    /**
     * Trägt der Mieter+Zeitraum-Block im Item-Formular Mieter und Zeitraum
     * ein, wird eine ZUSÄTZLICHE Vermietvorgang-Zuordnung angelegt.
     *
     * @return array{0: bool, 1: ?string} [wurde zugeordnet, Fehlergrund]
     */
    private function maybeAttachVerleih(Request $request, Item $item): array
    {
        if (! $request->filled('mieter_id') || ! $request->filled('verleih_start') || ! $request->filled('verleih_end')) {
            return [false, null];
        }

        $verleihStart = Carbon::createFromFormat('d.m.Y', $request->verleih_start)->format('Y-m-d');
        $verleihEnd = Carbon::createFromFormat('d.m.Y', $request->verleih_end)->format('Y-m-d');

        $result = $item->syncVermietvorgang((int) $request->mieter_id, $verleihStart, $verleihEnd);

        return [$result['added'], ($result['added'] || $result['alreadyAttached']) ? null : $result['reason']];
    }
}
