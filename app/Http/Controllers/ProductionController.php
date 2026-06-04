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

    $unitFilter = $request->get('unit', null);

    $availableItems = Item::whereDoesntHave('productions', function ($query) use ($id) {
        $query->where('production_id', $id);
    })->whereDoesntHave('cameraConfigs', function ($query) use ($id) {
        $query->where('production_id', $id);
    });

    if ($unitFilter) {
        $availableItems->where('units_id', $unitFilter);
    }

    $availableItems = $availableItems->get();
    $allUnits = Unit::all();

    return view('productions.show', compact('production', 'availableItems', 'unitFilter', 'allUnits'));
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
    
            // Retrieve booking dates
            $newStart = $production->booking_start;
            $newEnd = $production->booking_end;
    
            // Retrieve item details
            $item = Item::findOrFail($request->item_id);
    
            if ($item->is_rented) {
                // Validate rental period overlap
                $rentStart = $item->rent_start;
                $rentEnd = $item->rent_end;
            
    
                if (!($rentStart <= $newStart && $rentEnd >= $newEnd)) {
                    // Production period falls outside the rental period
                    return redirect()->route('productions.show', [
                        'production' => $id,
                        'unit' => $request->unit,
                    ])->with('error', 'Das gemietete Item kann nicht zugewiesen werden, da der Produktionszeitraum außerhalb des Mietzeitraums liegt.');
                }
            } else {
                // Check for booking conflicts for owned items
                $conflict = DB::table('item_production')
                    ->join('productions', 'item_production.production_id', '=', 'productions.id')
                    ->where('item_production.item_id', $request->item_id)
                    ->where(function ($query) use ($newStart, $newEnd) {
                        $query->where('productions.booking_start', '<=', $newEnd)
                              ->where('productions.booking_end', '>=', $newStart);
                    })
                    ->exists();
    
                if ($conflict) {
                    return redirect()->route('productions.show', [
                        'production' => $id,
                        'unit' => $request->unit,
                    ])->with('error', 'Das Item ist im angegebenen Zeitraum bereits gebucht.');
                }
            }
    
            // Perform the attachment in a transaction
            DB::transaction(function () use ($production, $request) {
                $production->items()->attach($request->item_id);
            });
    
            return redirect()->route('productions.show', [
                'production' => $id,
                'unit' => $request->unit,
            ])->with('success', 'Item erfolgreich zugewiesen.');
        } catch (ModelNotFoundException $e) {
            return redirect()->route('productions.index')->with('error', 'Production oder Item nicht gefunden.');
        } catch (\Exception $e) {
            return redirect()->route('productions.index')->with('error', 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.');
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


}

