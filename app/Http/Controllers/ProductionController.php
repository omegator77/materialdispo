<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttachItemRequest;
use App\Http\Requests\ProductionRequest;
use App\Http\Requests\StoreProductionImportRequest;
use App\Models\Item;
use App\Models\Production;
use App\Models\Unit;
use App\Services\ItemAvailabilityService;
use App\Services\ProductionAssignmentService;
use App\Services\ProductionImportService;
use App\Services\SlackVorgangSync;
use App\Services\SmartAssignmentService;
use App\Support\PdfFilename;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;

class ProductionController extends Controller
{
    public function __construct(
        private ItemAvailabilityService $availability,
        private ProductionImportService $imports,
        private SlackVorgangSync $slack,
        private ProductionAssignmentService $assignment,
    ) {}

    public function index(Request $request)
    {
        $selectedProduction = $request->get('production_id');

        if ($selectedProduction) {
            $productions = Production::with(Production::LIST_RELATIONS)->where('id', $selectedProduction)->get();
        } else {
            $productions = Production::with(Production::LIST_RELATIONS)->orderBy('bezeichnung')->get();
        }

        return view('productions.index', [
            'productions' => $productions,
            'allProductions' => Production::orderBy('bezeichnung')->get(),
            'selectedProduction' => $selectedProduction,
        ]);
    }

    public function create(Request $request)
    {
        $productions = Production::with(Production::LIST_RELATIONS)->orderBy('booking_start', 'desc')->limit(25)->get();
        $preset = $request->filled('from') ? Production::find($request->from) : null;

        return view('productions.create', compact('productions', 'preset'));
    }

    public function searchTemplates(Request $request)
    {
        $query = trim((string) $request->get('q', ''));
        $exclude = array_values(array_filter(array_map('intval', explode(',', (string) $request->get('exclude', '')))));

        $productions = Production::query()
            ->when($query !== '', fn ($q) => $q->where('bezeichnung', 'like', "%{$query}%"))
            ->when($exclude !== [], fn ($q) => $q->whereNotIn('id', $exclude))
            ->orderBy('booking_start', 'desc')
            ->limit(20)
            ->get(['id', 'bezeichnung', 'booking_start']);

        return response()->json($productions->map(fn ($p) => [
            'id' => $p->id,
            'bezeichnung' => $p->bezeichnung,
            'booking_start' => $p->booking_start ? Carbon::parse($p->booking_start)->format('d.m.Y') : null,
        ]));
    }

    public function store(ProductionRequest $request)
    {
        $bookingStart = Carbon::createFromFormat('d.m.Y', $request->booking_start)->format('Y-m-d');
        $bookingEnd = Carbon::createFromFormat('d.m.Y', $request->booking_end ?: $request->booking_start)->format('Y-m-d');

        $production = Production::create([
            'bezeichnung' => $request->input('bezeichnung'),
            'booking_start' => $bookingStart,
            'booking_end' => $bookingEnd,
            'packlist_notes' => $request->packlist_notes,
        ]);

        $this->slack->syncProduction($production);

        if ($request->filled('from_production_id')) {
            $source = Production::find($request->from_production_id);
            if ($source) {
                return redirect()->route('productions.importFrom', [$production, $source]);
            }
        }

        return redirect()->route('productions.show', $production)->with('focus_quick_add', true);
    }

    public function show($id, Request $request)
    {
        $production = Production::with([
            'items.unit',
            'items.mietvorgaenge.supplier',
            'items.vermietvorgaenge.mieter',
            'cameraConfigs.item.unit',
            'cameraConfigs.lensItem',
            'cameraConfigs.tripodItem',
            'cameraConfigs.headItem',
            'cameraConfigs.adapterItem',
        ])->findOrFail($id);

        $unitFilter = $request->get('unit');

        $availableItems = $this->availability->assignableItemsFor(
            $production,
            $unitFilter,
            $request->boolean('show_unavailable')
        );

        $allUnits = Unit::ordered()->get();

        $mietvorgaenge = $production->items->pluck('mietvorgaenge')->flatten()->unique('id')->values();
        $vermietvorgaenge = $production->items->pluck('vermietvorgaenge')->flatten()->unique('id')->values();

        $smart = app(SmartAssignmentService::class);
        $suggestions = $smart->suggestionsFor($production);
        $hasVbAnforderungen = $production->vbProtokoll()->whereHas('anforderungen')->exists();
        $similarProduction = $production->packlistEntries()->count() < 3 ? $smart->similarProduction($production) : null;

        return view('productions.show', compact(
            'suggestions',
            'hasVbAnforderungen',
            'similarProduction',
            'production',
            'availableItems',
            'unitFilter',
            'allUnits',
            'mietvorgaenge',
            'vermietvorgaenge'
        ));
    }

    public function edit(Production $production)
    {
        $productions = Production::with(Production::LIST_RELATIONS)->get();

        return view('productions.edit', compact('production', 'productions'));
    }

    public function update(ProductionRequest $request, Production $production)
    {
        $bookingStart = Carbon::createFromFormat('d.m.Y', $request->booking_start)->format('Y-m-d');
        $bookingEnd = Carbon::createFromFormat('d.m.Y', $request->booking_end ?: $request->booking_start)->format('Y-m-d');

        $production->update([
            'bezeichnung' => $request->input('bezeichnung'),
            'booking_start' => $bookingStart,
            'booking_end' => $bookingEnd,
            'packlist_notes' => $request->packlist_notes,
        ]);

        $this->slack->syncProduction($production);

        return redirect('/productions')->with('success', 'Produktion erfolgreich aktualisiert.');
    }

    public function destroy(string $id)
    {
        // Model-Delete (nicht Query-Builder), damit Eloquent-Events feuern und
        // die Löschung im Activity-Log landet — eine Produktion zu löschen ist
        // ein weitreichender Schritt (DB-Cascade räumt Packliste, Kamerazüge,
        // VB-Protokoll, Packstatus, Fahrzeuge, Buchungen mit auf).
        $production = Production::findOrFail($id);
        $production->delete();

        return redirect('/productions');
    }

    public function attachItem(AttachItemRequest $request, $id)
    {
        $itemIds = collect((array) $request->input('item_id'))->filter()->unique()->values();

        try {
            $production = Production::findOrFail($id);

            $redirectParams = [
                'production' => $production->id,
            ];

            if ($request->filled('unit')) {
                $redirectParams['unit'] = $request->unit;
            }

            if ($request->boolean('show_unavailable')) {
                $redirectParams['show_unavailable'] = 1;
            }

            $items = Item::whereIn('id', $itemIds)->get()->keyBy('id');

            if ($items->count() !== $itemIds->count()) {
                return redirect()
                    ->route('productions.show', $redirectParams)
                    ->with('error', 'Ein oder mehrere Geräte wurden nicht gefunden.');
            }

            $result = $this->assignment->attachMany($production, $items->values(), $request->notes);

            $added = array_map(fn ($item) => $item->bezeichnung, $result['added']);
            $skipped = array_map(fn ($s) => "{$s['item']->bezeichnung} ({$s['reason']})", $result['skipped']);
            $warnings = $result['warnings'];

            $messageParts = [];
            if (count($added)) {
                $messageParts[] = count($added).' Gerät(e) hinzugefügt: '.implode(', ', $added);
            }
            if (count($skipped)) {
                $messageParts[] = 'Übersprungen: '.implode(', ', $skipped);
            }

            $messageType = count($added) ? 'success' : 'error';
            $message = $messageParts ? implode(' — ', $messageParts) : 'Keine Geräte ausgewählt.';

            return redirect()
                ->route('productions.show', $redirectParams)
                ->with($messageType, $message)
                ->with('warning', $warnings ? 'Hinweis: '.implode(', ', $warnings) : null);
        } catch (ModelNotFoundException $e) {
            return redirect()
                ->route('productions.index')
                ->with('error', 'Produktion oder Item nicht gefunden.');
        } catch (\Exception $e) {
            report($e);

            return redirect()
                ->route('productions.index')
                ->with('error', 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.');
        }
    }

    public function detachItem($id, $itemId)
    {
        $production = Production::findOrFail($id);
        $item = Item::find($itemId);
        $production->items()->detach($itemId);

        $this->slack->syncProduction($production);

        if ($item) {
            activity('item')
                ->performedOn($item)
                ->event('detached')
                ->withProperties(['production' => $production->bezeichnung, 'production_id' => $production->id])
                ->log("Gerät \"{$item->bezeichnung}\" aus Produktion \"{$production->bezeichnung}\" entfernt");
        }

        return Redirect::route('productions.show', ['production' => $id])
            ->with('success', 'Item erfolgreich entfernt.');
    }

    public function generatePDF($id)
    {
        $production = Production::with([
            'items.unit',
            'cameraConfigs.item.unit',
            'cameraConfigs.lensItem',
            'cameraConfigs.tripodItem',
            'cameraConfigs.headItem',
            'cameraConfigs.adapterItem',
        ])->findOrFail($id);

        $data = [
            'production' => $production,
            'items' => $production->items,
            'cameraConfigs' => $production->cameraConfigs,
        ];

        $pdf = Pdf::loadView('pdf.production_items', $data);

        return $pdf->download(PdfFilename::make($production->bezeichnung));
    }

    public function importFrom(Production $production, Production $source)
    {
        $preview = $this->imports->buildImportPreview($production, $source);

        return view('productions.import-from', [
            'production' => $production,
            'source' => $source,
            'itemResults' => $preview['itemResults'],
            'configResults' => $preview['configResults'],
        ]);
    }

    public function storeImport(StoreProductionImportRequest $request, Production $production, Production $source)
    {
        $result = $this->imports->applyImport(
            $production,
            $source,
            $request->validated('items', []),
            $request->validated('configs', [])
        );

        if ($result['imported'] > 0) {
            $this->slack->syncProduction($production);
        }

        return redirect()->route('productions.show', $production)
            ->with('success', $result['imported'].' Position(en) aus Vorlage übernommen.')
            ->with('warning', $result['skipped'] ? 'Nicht übernommen: '.implode(', ', $result['skipped']) : null);
    }
}
