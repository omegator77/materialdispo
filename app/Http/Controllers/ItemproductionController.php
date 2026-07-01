<?php

namespace App\Http\Controllers;

use App\Models\Itemproduction;
use App\Models\Item;
use App\Models\Unit;
use App\Models\Production;
use App\Models\CameraConfig;
use Illuminate\Http\Request;

class ItemproductionController extends Controller
{

    public function index(Request $request)
{
    // Grundlegende Query mit Beziehungen für ItemProductions
    $itemQuery = ItemProduction::with(['production', 'item.unit']);

    // Grundlegende Query mit Beziehungen für Kamera-Konfigurationen
    $configQuery = CameraConfig::with(['production', 'item.unit']);

    // Filter nach Produktion
    if ($request->has('production_id') && $request->production_id) {
        $itemQuery->where('production_id', $request->production_id);
        $configQuery->where('production_id', $request->production_id);
    }

    // Filter nach Gruppe (Unit)
    if ($request->has('unit_id') && $request->unit_id) {
        $itemQuery->whereHas('item.unit', function ($q) use ($request) {
            $q->where('id', $request->unit_id);
        });

        $configQuery->whereHas('item.unit', function ($q) use ($request) {
            $q->where('id', $request->unit_id);
        });
    }

    // Filter nach Gerät (Item)
    if ($request->has('item_id') && $request->item_id) {
        $itemQuery->where('item_id', $request->item_id);
        $configQuery->where('item_id', $request->item_id);
    }

    // Ergebnisse abrufen
    $itemproductions = $itemQuery->get();
    $cameraConfigs = $configQuery->get();

    // Alle Produktionen, Geräte und Gruppen für die Filteroptionen abrufen
    $allProductions = Production::with([
        'items.unit',
        'cameraConfigs.item.unit',
        'cameraConfigs.lensItem',
        'cameraConfigs.tripodItem',
        'cameraConfigs.headItem',
        'cameraConfigs.adapterItem',
        'itemPacks',
    ])->orderBy('booking_start')->get();
    $allUnits = Unit::orderBy('bezeichnung')->get();
    $allItems = Item::orderBy('bezeichnung')->get();

    // View mit Daten zurückgeben
    return view('itemproductions.index', [
        'itemproductions' => $itemproductions,
        'cameraConfigs' => $cameraConfigs,
        'allProductions' => $allProductions,
        'allUnits' => $allUnits,
        'allItems' => $allItems,
        'filters' => $request->only(['production_id', 'unit_id', 'item_id']),
    ]);
}

    
/*    public function index(){
        $itemproductions = Itemproduction::all();
       // dd($itemproductions);
       return view('itemproductions.index', ['itemproductions'=> $itemproductions]);
    }  */
}
