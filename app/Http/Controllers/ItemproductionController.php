<?php

namespace App\Http\Controllers;

use App\Models\Itemproduction;
use App\Models\Item;
use App\Models\Unit;
use App\Models\Production;
use Illuminate\Http\Request;

class ItemproductionController extends Controller
{

    public function index(Request $request)
{
    // Grundlegende Query mit Beziehungen
    $query = ItemProduction::with(['production', 'item.unit']);

    // Filter nach Produktion
    if ($request->has('production_id') && $request->production_id) {
        $query->where('production_id', $request->production_id);
    }

    // Filter nach Gruppe (Unit)
    if ($request->has('unit_id') && $request->unit_id) {
        $query->whereHas('item.unit', function ($q) use ($request) {
            $q->where('id', $request->unit_id);
        });
    }

    // Filter nach Gerät (Item)
    if ($request->has('item_id') && $request->item_id) {
        $query->where('item_id', $request->item_id);
    }

    // Ergebnisse abrufen
    $itemproductions = $query->get();

    // Alle Produktionen, Geräte und Gruppen für die Filteroptionen abrufen
    $allProductions = Production::all();
    $allUnits = Unit::all();
    $allItems = Item::all();

    // View mit Daten zurückgeben
    return view('itemproductions.index', [
        'itemproductions' => $itemproductions,
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
