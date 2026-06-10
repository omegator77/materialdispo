<?php

namespace App\Http\Controllers;
use App\Models\Production;
use App\Models\Item;
use App\Models\Unit;
use App\Models\CameraConfig;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

use Illuminate\Http\Request;

class ProductionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
{
    $selectedProduction = $request->get('production_id', null);

    if ($selectedProduction) {
        $productions = Production::where('id', $selectedProduction)->get();
    } else {
        $productions = Production::all();
    }

    return view('productions.index', [
        'productions' => $productions,
        'allProductions' => Production::all(), // Um alle Produktionen anzuzeigen
        'selectedProduction' => $selectedProduction,
    ]);
}

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $productions = Production::all();
      
        return view('productions.create', compact('productions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'bezeichnung' => 'required',
            'booking_start' => 'date_format:d.m.Y|required',
            'booking_end' => 'date_format:d.m.Y|after_or_equal:booking_start|required',
            
            ]);

            // Die Datumsfelder für die Speicherung in der Datenbank formatieren
$bookingStart = $request->booking_start ? \Carbon\Carbon::createFromFormat('d.m.Y', $request->booking_start)->format('Y-m-d') : null;
$bookingEnd = $request->booking_end ? \Carbon\Carbon::createFromFormat('d.m.Y', $request->booking_end)->format('Y-m-d') : null;

        Production::create([
            'bezeichnung'=>$request->input('bezeichnung'),
            'booking_start'=> $bookingStart,
            'booking_end'=> $bookingEnd,
            
        ]);

        return redirect('/productions');
    }

    /**
     * Display the specified resource.
     */
    /**
 * Display the specified resource.
 */
public function show($id, Request $request)
{
    $production = Production::with([
        'items.unit',
        'cameraConfigs.item.unit',
        'cameraConfigs.lensItem',
        'cameraConfigs.tripodItem',
        'cameraConfigs.headItem',
        'cameraConfigs.adapterItem',
    ])->findOrFail($id);

    $unitFilter = $request->get('unit');
    $showUnavailable = $request->boolean('show_unavailable');

    /*
     * Items laden, die in DIESER Produktion noch nicht gepackt sind.
     *
     * Wichtig:
     * - Direkt gepackte Items aus dieser Produktion werden ausgeblendet.
     * - Items aus CameraConfigs dieser Produktion werden ebenfalls ausgeblendet.
     * - Items aus ANDEREN Produktionen bleiben erstmal drin.
     *   Ihre Verfügbarkeit wird danach geprüft.
     */
    $itemsQuery = Item::query()
        ->whereDoesntHave('productions', function ($query) use ($id) {
            $query->where('productions.id', $id);
        })
        ->where(function ($query) use ($id) {
            $query
                ->whereDoesntHave('cameraConfigs', function ($q) use ($id) {
                    $q->where('production_id', $id);
                })
                ->whereNotIn('id', function ($q) use ($id) {
                    $q->select('lens')
                        ->from('camera_configs')
                        ->where('production_id', $id)
                        ->whereNotNull('lens');
                })
                ->whereNotIn('id', function ($q) use ($id) {
                    $q->select('tripod')
                        ->from('camera_configs')
                        ->where('production_id', $id)
                        ->whereNotNull('tripod');
                })
                ->whereNotIn('id', function ($q) use ($id) {
                    $q->select('tripod_head')
                        ->from('camera_configs')
                        ->where('production_id', $id)
                        ->whereNotNull('tripod_head');
                })
                ->whereNotIn('id', function ($q) use ($id) {
                    $q->select('large_lens_adapter')
                        ->from('camera_configs')
                        ->where('production_id', $id)
                        ->whereNotNull('large_lens_adapter');
                });
        });

    if ($unitFilter) {
        $itemsQuery->where('units_id', $unitFilter);
    }

    /*
     * Verfügbarkeit markieren:
     * - verfügbar: normal auswählbar
     * - nicht verfügbar: disabled im Dropdown
     */
    $availableItems = $itemsQuery
        ->orderBy('bezeichnung')
        ->get()
        ->map(function ($item) use ($production) {
            $availability = $this->checkItemAvailability($item, $production);

            $item->is_available = $availability['available'];
            $item->availability_reason = $availability['reason'];

            return $item;
        });

    /*
     * Standardverhalten:
     * - nicht verfügbare Items ausblenden
     *
     * Wenn show_unavailable=1 gesetzt ist:
     * - nicht verfügbare Items anzeigen, aber disabled
     */
    if (! $showUnavailable) {
        $availableItems = $availableItems
            ->filter(fn ($item) => $item->is_available)
            ->values();
    }

    $allUnits = Unit::orderBy('bezeichnung')->get();

    return view('productions.show', compact(
        'production',
        'availableItems',
        'unitFilter',
        'allUnits'
    ));
}
    

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Production $production)
    {
        $productions = Production::all();
        return view('productions.edit', compact('production', 'productions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Production $production)
{
    try{
    // Validierung der Eingaben
    $request->validate([
        'bezeichnung' => 'required',
        'booking_start' => 'date_format:d.m.Y|required',
        'booking_end' => 'date_format:d.m.Y|after_or_equal:booking_start|required',
    ]);

    // Die Datumsfelder für die Speicherung in der Datenbank formatieren
    $bookingStart = $request->booking_start 
        ? \Carbon\Carbon::createFromFormat('d.m.Y', $request->booking_start)->format('Y-m-d') 
        : null;

    $bookingEnd = $request->booking_end 
        ? \Carbon\Carbon::createFromFormat('d.m.Y', $request->booking_end)->format('Y-m-d') 
        : null;

    // Aktualisierung der Produktion
    $production->update([
        'bezeichnung' => $request->input('bezeichnung'),
        'booking_start' => $bookingStart,
        'booking_end' => $bookingEnd,
    ]);

    // Weiterleitung mit Erfolgsnachricht
    return redirect('/productions')->with('success', 'Produktion erfolgreich aktualisiert.');
}
    catch(Exeption $e) {
        return redirect()->back()->with('error', 'Fehler beim Aktualisieren: ' . $e->getMessage());
    }
}


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Production::where('id', $id)->delete();
        return redirect('/productions');
    }

    public function attachItem(Request $request, $id)
{
    $request->validate([
        'item_id' => 'required|exists:items,id',
    ]);

    try {
        $production = Production::findOrFail($id);
        $item = Item::findOrFail($request->item_id);

        /*
         * Aktuelle Filterauswahl merken,
         * damit sie nach dem Speichern erhalten bleibt.
         */
        $redirectParams = [
            'production' => $id,
            'unit' => $request->unit,
            'show_unavailable' => $request->show_unavailable,
        ];

        /*
         * Neue zentrale Verfügbarkeitsprüfung:
         * prüft Mietzeitraum, direkte Buchungen und CameraConfigs.
         */
        $availability = $this->checkItemAvailability($item, $production);

        if (! $availability['available']) {
            return redirect()
                ->route('productions.show', $redirectParams)
                ->with('error', $availability['reason']);
        }

        /*
         * Item zu Produktion hinzufügen.
         * syncWithoutDetaching verhindert versehentliche Doppel-Einträge.
         */
        DB::transaction(function () use ($production, $item) {
            $production->items()->syncWithoutDetaching([$item->id]);
        });

        return redirect()
            ->route('productions.show', $redirectParams)
            ->with('success', 'Item erfolgreich zugewiesen.');

    } catch (ModelNotFoundException $e) {
        return redirect()
            ->route('productions.index')
            ->with('error', 'Production oder Item nicht gefunden.');

    } catch (\Exception $e) {
        return redirect()
            ->route('productions.index')
            ->with('error', 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.');
    }
}
    
    


    public function detachItem($id, $itemId)
    {
        $production = Production::findOrFail($id);
        $production->items()->detach($itemId);

        return Redirect::route('productions.show', ['production' => $id])->with('success', 'Item erfolgreich entfernt.');
    }
    
    public function requirements($id)
    {
        // Lade die Produktion anhand der ID
        $production = Production::findOrFail($id);
    
        // Übergebe die Produktion an die View
        return view('productions.requirements', compact('production'));
    }
    
    public function generatePDF($id)
{
    // Produktion abrufen
    $production = Production::with('items')->findOrFail($id);

    // Kamera-Konfigurationen abrufen
    $cameraConfigs = CameraConfig::with('item.unit')
        ->where('production_id', $id)
        ->get();

    // Daten an die View übergeben
    $data = [
        'production' => $production,
        'items' => $production->items, // Alle gebuchten Items
        'cameraConfigs' => $cameraConfigs, // Kamera-Konfigurationen
    ];

    // PDF generieren
    $pdf = Pdf::loadView('pdf.production_items', $data);

    return $pdf->download("{$production->bezeichnung}.pdf");
}

public function storeCameraConfig(Request $request, Production $production)
{
    $validated = $request->validate([
        'cam_number'         => 'required|string|max:255',
        'camera'             => 'required|exists:items,id',
        'lens'               => 'nullable|exists:items,id',
        'tripod'             => 'nullable|exists:items,id',
        'tripod_head'        => 'nullable|exists:items,id',
        'large_lens_adapter' => 'nullable|exists:items,id',
        'notes'              => 'nullable|string|max:2000',
    ]);

    $selectedItemIds = collect([
    $validated['camera'],
    $validated['lens'] ?? null,
    $validated['tripod'] ?? null,
    $validated['tripod_head'] ?? null,
    $validated['large_lens_adapter'] ?? null,
])
    ->filter()
    ->unique();

foreach ($selectedItemIds as $itemId) {
    $item = Item::findOrFail($itemId);

    $availability = $this->checkItemAvailability($item, $production);

    if (! $availability['available']) {
        return redirect()
            ->back()
            ->withInput()
            ->with(
                'error',
                $item->bezeichnung . ': ' . $availability['reason']
            );
    }
}

    // Neues CameraConfig erstellen
    $config = new CameraConfig();
    $config->production_id      = $production->id;
    $config->item_id            = $validated['camera'];   // die gewählte Kamera
    $config->lens               = $validated['lens'] ?? null;
    $config->tripod             = $validated['tripod'] ?? null;
    $config->tripod_head        = $validated['tripod_head'] ?? null;
    $config->large_lens_adapter = $validated['large_lens_adapter'] ?? null;
    $config->cam_number         = $validated['cam_number'];
    $config->notes              = $validated['notes'] ?? null;
    $config->save();

    return redirect()
        ->route('productions.show', $production)
        ->with('success', 'Kamera-Konfiguration gespeichert.');
}


public function createCameraConfig(Production $production, Request $request)
{
    // Vorauswahl aus der Show-Seite: .../camera-config/create?camera_item_id=123
    $preselectedCameraId = (int) $request->query('camera_item_id');

    if ($preselectedCameraId) {
    $camera = Item::findOrFail($preselectedCameraId);

    $availability = $this->checkItemAvailability($camera, $production);

    if (! $availability['available']) {
        return redirect()
            ->route('productions.show', $production)
            ->with('error', $camera->bezeichnung . ': ' . $availability['reason']);
    }
}

    // Dropdown-Daten anhand deiner units_id aus dem Dump:
    $cameras  = Item::where('units_id', 1)->orderBy('bezeichnung')->get();
    $lenses   = Item::where('units_id', 2)->orderBy('bezeichnung')->get();
    $tripods  = Item::where('units_id', 3)->orderBy('bezeichnung')->get();
    $heads    = Item::where('units_id', 4)->orderBy('bezeichnung')->get();
    $adapters = Item::where('units_id', 5)->orderBy('bezeichnung')->get();

    return view('camera_configs.create', compact(
        'production', 'cameras', 'lenses', 'tripods', 'heads', 'adapters', 'preselectedCameraId'
    ));
}

private function cameraConfigItemColumns(): array
{
    return ['item_id', 'lens', 'tripod', 'tripod_head', 'large_lens_adapter'];
}

private function itemIsInCameraConfigQuery($query, int $itemId)
{
    $query->where(function ($q) use ($itemId) {
        foreach ($this->cameraConfigItemColumns() as $column) {
            $q->orWhere($column, $itemId);
        }
    });
}

private function checkItemAvailability(Item $item, Production $production): array
{
    // Mietgerät außerhalb des Mietzeitraums
    if ($item->is_rented) {

        if (
            $item->rent_start &&
            $item->rent_start > $production->booking_start
        ) {
            return [
                'available' => false,
                'reason' => 'Mietbeginn zu spät',
            ];
        }

        if (
            $item->rent_end &&
            $item->rent_end < $production->booking_end
        ) {
            return [
                'available' => false,
                'reason' => 'Mietende zu früh',
            ];
        }
    }

    // Direkte Buchungskonflikte
    $conflict = $item->productions()
        ->where('productions.id', '!=', $production->id)
        ->where('booking_start', '<=', $production->booking_end)
        ->where('booking_end', '>=', $production->booking_start)
        ->first();

    if ($conflict) {
        return [
            'available' => false,
            'reason' => 'Gebucht in Produktion: ' . $conflict->bezeichnung,
        ];
    }

    // CameraConfig-Konflikte
    $configConflict = CameraConfig::query()
        ->where('production_id', '!=', $production->id)
        ->whereHas('production', function ($q) use ($production) {
            $q->where('booking_start', '<=', $production->booking_end)
              ->where('booking_end', '>=', $production->booking_start);
        });

    $this->itemIsInCameraConfigQuery($configConflict, $item->id);

    $configConflict = $configConflict
        ->with('production')
        ->first();

    if ($configConflict) {
        return [
            'available' => false,
            'reason' => 'In Kamerakonfiguration: ' . $configConflict->production->bezeichnung,
        ];
    }

    return [
        'available' => true,
        'reason' => null,
    ];
}


}

