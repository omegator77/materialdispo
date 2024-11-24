<?php

namespace App\Http\Controllers;
use App\Models\Production;
use App\Models\Item;
use App\Models\Unit;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\DB;

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
        //  \Log::info('Aktuelle Gruppenauswahl: ', ['unit' => $request->get('unit')]);
        $production = Production::with('items')->findOrFail($id);
    
        $unitFilter = $request->get('unit', null);
    
        $availableItems = Item::whereDoesntHave('productions', function ($query) use ($id) {
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
        return view('productions.edit', compact('production'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Production $production)
{
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
    
        $production = Production::findOrFail($id);
    
        // Daten der neuen Buchung
        $newStart = $production->booking_start;
        $newEnd = $production->booking_end;
    
        // Prüfen, ob das Item bereits für diesen Zeitraum gebucht ist
        $conflict = DB::table('item_production')
            ->join('productions', 'item_production.production_id', '=', 'productions.id')
            ->where('item_production.item_id', $request->item_id)
            ->where(function ($query) use ($newStart, $newEnd) {
                $query->where('productions.booking_start', '<=', $newEnd)
                      ->where('productions.booking_end', '>=', $newStart);
            })
            ->exists();
           
            //dd($newStart, $newEnd, $conflict);
            
    
        if ($conflict) {
            
            return redirect()->route('productions.show', [
                'production' => $id,
                'unit' => $request->unit,
            ])->with('error', 'Das Item ist im angegebenen Zeitraum bereits gebucht.');
        }
    
        // Zuweisung durchführen
        $production->items()->attach($request->item_id);
    
        return redirect()->route('productions.show', [
            'production' => $id,
            'unit' => $request->unit,
        ])->with('success', 'Item erfolgreich zugewiesen.');
    }
    


    public function detachItem($id, $itemId)
    {
        $production = Production::findOrFail($id);
        $production->items()->detach($itemId);

        return Redirect::route('productions.show', ['production' => $id])->with('success', 'Item erfolgreich entfernt.');
    }
    

}

