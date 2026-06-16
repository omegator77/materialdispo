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

        // Benutzerdefinierte Sortierung
        if ($request->has('sort_by') && in_array($request->sort_by, ['bezeichnung', 'nummer', 'units_id', 'rent_start', 'rent_end'])) {
            $sortDirection = $request->get('sort_direction', 'asc');
            $query->orderBy('units_id') // Zuerst immer nach units_id gruppieren
                ->orderBy($request->sort_by, $sortDirection); // Dann benutzerdefinierte Sortierung
        } else {
            // Standard-Sortierung, falls nichts angegeben ist
            $query->orderBy('units_id', 'asc')
                ->orderBy('nummer', 'asc'); // Zweite Sortierung nach nummer
        }

        $items = $query->get();
        $Units = Unit::all();

        return view('items.index', ['items' => $items, 'units' => $Units]);
    }




    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $units = Unit::all(); // Alle Units abrufen
        $suppliers = Supplier::all(); // Alle Supplier abrufen
        $items = Item::all();
        return view('items.create', compact('units', 'suppliers', 'items')); // Beide Sammlungen zur View übergeben

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'units_id' => 'required|exists:units,id',
            'suppliers_id' => 'nullable|exists:suppliers,id',
            'bezeichnung' => 'required',
            'rent_start' => 'required_with:suppliers_id|date_format:d.m.Y',
            'rent_end' => 'required_with:suppliers_id|date_format:d.m.Y|after_or_equal:rent_start',
        ]);

        /*
     * Mietlogik:
     * Ein Item gilt als Mietmaterial, sobald ein Vermieter angegeben ist.
     * Ohne Vermieter werden Mietbeginn und Mietende automatisch gelöscht.
     */
        $supplierId = $request->suppliers_id ?: null;

        $rentStart = $supplierId && $request->rent_start
            ? \Carbon\Carbon::createFromFormat('d.m.Y', $request->rent_start)->format('Y-m-d')
            : null;

        $rentEnd = $supplierId && $request->rent_end
            ? \Carbon\Carbon::createFromFormat('d.m.Y', $request->rent_end)->format('Y-m-d')
            : null;

        Item::create([
            'units_id'      => $request->units_id,
            'suppliers_id'  => $supplierId,
            'bezeichnung'   => $request->bezeichnung,
            'nummer'        => $request->nummer,
            'description'   => $request->description,
            'rent_start'    => $rentStart,
            'rent_end'      => $rentEnd,
        ]);

        return redirect(route('items.index'));
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $item = Item::with(['productions', 'cameraDetail'])->findOrFail($id);

        return view('items.show', compact('item'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $units = Unit::all(); // Alle Units abrufen
        $suppliers = Supplier::all(); // Alle Supplier abrufen
        $item = Item::with('cameraDetail')->findOrFail($id);

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
        $item = Item::findOrFail($id);

        $request->validate([
            'bezeichnung' => 'required',
            'nummer' => 'nullable',
            'suppliers_id' => 'nullable|exists:suppliers,id',
            'rent_start' => 'required_with:suppliers_id|date_format:d.m.Y',
            'rent_end' => 'required_with:suppliers_id|date_format:d.m.Y|after_or_equal:rent_start',
            'body_serial' => 'nullable|string|max:255',
            'fiber_adapter_serial' => 'nullable|string|max:255',
            'large_viewfinder_model' => 'nullable|string|max:255',
            'large_viewfinder_type' => 'nullable|in:OLED,LCD',
            'large_viewfinder_serial' => 'nullable|string|max:255',
            'small_viewfinder_model' => 'nullable|string|max:255',
            'small_viewfinder_type' => 'nullable|in:OLED,LCD',
            'small_viewfinder_serial' => 'nullable|string|max:255',
            'ssl_license' => 'nullable|boolean',
        ]);

        /*
     * Mietlogik:
     * Ein Item gilt als Mietmaterial, sobald ein Vermieter angegeben ist.
     * Wird der Vermieter entfernt, werden Mietbeginn und Mietende gelöscht.
     */
        $supplierId = $request->suppliers_id ?: null;

        $rentStart = $supplierId && $request->rent_start
            ? \Carbon\Carbon::createFromFormat('d.m.Y', $request->rent_start)->format('Y-m-d')
            : null;

        $rentEnd = $supplierId && $request->rent_end
            ? \Carbon\Carbon::createFromFormat('d.m.Y', $request->rent_end)->format('Y-m-d')
            : null;

        $item->update([
            'units_id'      => $request->units_id,
            'suppliers_id'  => $supplierId,
            'bezeichnung'   => $request->bezeichnung,
            'nummer'        => $request->nummer,
            'description'   => $request->description,
            'rent_start'    => $rentStart,
            'rent_end'      => $rentEnd,
        ]);

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
        } else {
            $item->cameraDetail()->delete();
        }

        return redirect('items');
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
