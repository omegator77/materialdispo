<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Unit;
use App\Models\Supplier;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Item::with(['unit', 'supplier']);

        // Filter nach Gruppe (Unit)
        if ($request->has('unit_id') && $request->unit_id) {
            $query->where('units_id', $request->unit_id);
        }

        // Sortierung
        if ($request->has('sort_by') && in_array($request->sort_by, ['bezeichnung', 'nummer', 'units_id', 'rent_start', 'rent_end'])) {
            $sortDirection = $request->get('sort_direction', 'asc');
            $query->orderBy($request->sort_by, $sortDirection);
        } else {
            $query->orderBy('units_id', 'asc');
        }

        $items = $query->get();
        $allUnits = Unit::all();
        
        return view('items.index', ['items' => $items, 'allUnits' => $allUnits]);
    }



    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $units = Unit::all(); // Alle Units abrufen
        $suppliers = Supplier::all(); // Alle Supplier abrufen
        return view('items.create', compact('units', 'suppliers')); // Beide Sammlungen zur View übergeben
        
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'units_id' => 'required|exists:units,id',
            'suppliers_id' => 'exists:suppliers,id|nullable',
            // Validierung der Datumsfelder im Format TT.MM.JJJJ
            'rent_start' => 'date_format:d.m.Y|nullable',
            'rent_end' => 'date_format:d.m.Y|after_or_equal:rent_start|nullable',
        ]);

        // Die Datumsfelder für die Speicherung in der Datenbank formatieren
$rentStart = $request->rent_start ? \Carbon\Carbon::createFromFormat('d.m.Y', $request->rent_start)->format('Y-m-d') : null;
$rentEnd = $request->rent_end ? \Carbon\Carbon::createFromFormat('d.m.Y', $request->rent_end)->format('Y-m-d') : null;


        Item::create([
            'units_id' => $request->units_id,
            'suppliers_id' => $request->suppliers_id,
            'bezeichnung' => $request->bezeichnung,
            'nummer' => $request->nummer,
            'description' => $request->description,
            'is_rented' => $request->has('is_rented') ? 1 : 0, // Setze auf 1 oder 0
            'rent_start' => $rentStart,
            'rent_end' => $rentEnd,
            // Weitere Felder speichern
        ]);

        return redirect(route('items.index'));
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $item = Item::with('productions')->findOrFail($id);
        return view('items.show', compact('item'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $units = Unit::all(); // Alle Units abrufen
        $suppliers = Supplier::all(); // Alle Supplier abrufen
        $item = Item::findOrFail($id);

        // Datumsfelder formatieren

        $item->rent_start = $item->rent_start ? \Carbon\Carbon::parse($item->rent_start)->format('d.m.Y') : null;
        $item->rent_end = $item->rent_end ? \Carbon\Carbon::parse($item->rent_end)->format('d.m.Y') : null;

        return view('items.edit', compact('units', 'suppliers', 'item'));
        //return view('items.edit', compact('item'));
        //return redirect('/units');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $units = Unit::all(); // Alle Units abrufen
        $suppliers = Supplier::all(); // Alle Supplier abrufen
        $item = Item::findOrFail($id);

        $request->validate([
            'bezeichnung' => 'required',
            'nummer' => 'nullable',
            'rent_start' => 'nullable',
            'rent_end' => 'nullable',
        ]);
    
        // Die Datumsfelder für die Speicherung in der Datenbank formatieren

            $rentStart = $request->rent_start ? \Carbon\Carbon::createFromFormat('d.m.Y', $request->rent_start)->format('Y-m-d') : null;
            $rentEnd = $request->rent_end ? \Carbon\Carbon::createFromFormat('d.m.Y', $request->rent_end)->format('Y-m-d') : null;

        //$item = Item::findOrFail($id);
        $item->update([
            'units_id' => $request->units_id,
            'suppliers_id' => $request->suppliers_id,
            'bezeichnung' => $request->input('bezeichnung'),
            'nummer' => $request->input('nummer'),
            'description' => $request->input('description'),
            'is_rented' => $request->has('is_rented') ? 1 : 0, // Setze auf 1 oder 0
            'rent_start' => $rentStart ?: null,
            'rent_end' => $rentEnd ?: null,
        ]);
    
        return redirect('items');
       // dd($item);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Item::where('id', $id)->delete();
        return redirect('/items');
    }
}
