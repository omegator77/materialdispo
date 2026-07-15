<?php

namespace App\Http\Controllers;

use App\Http\Requests\UnitRequest;
use App\Models\Unit;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $units = Unit::orderBy('sort_order')->orderBy('bezeichnung')->get();

        return view('units.index', ['units' => $units]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $units = Unit::all();
        $items = Item::all();

        return view('units.create', compact('units', 'items'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UnitRequest $request)
    {
        Unit::create($request->validated());

        return redirect()->route('units.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Unit $unit)
    {
        return view('units.show', compact('unit'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Unit $unit)
    {
        $units = Unit::all();

        return view('units.edit', compact('unit', 'units'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UnitRequest $request, Unit $unit)
    {
        $unit->update($request->validated());

        return redirect()->route('units.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Unit $unit)
    {
        // items.units_id ist RESTRICT: eine Gruppe mit zugeordneten Geräten lässt
        // sich auf DB-Ebene nicht löschen. Sauber abfangen statt 500.
        try {
            $unit->delete();
        } catch (QueryException $e) {
            return redirect()
                ->route('units.index')
                ->with('error', 'Dieser Gruppe sind noch Geräte zugeordnet — sie kann nicht gelöscht werden.');
        }

        return redirect()->route('units.index')->with('success', 'Gruppe gelöscht.');
    }

    /**
     * Verschiebt eine Gruppe in der Sortierreihenfolge um eine Position nach
     * oben oder unten, indem ihr sort_order mit dem der Nachbargruppe
     * getauscht wird. Am Rand (schon erste/letzte Gruppe) ein No-op.
     */
    public function reorder(Request $request, Unit $unit)
    {
        $request->validate(['direction' => 'required|in:up,down']);

        $units = Unit::orderBy('sort_order')->orderBy('bezeichnung')->get();
        $position = $units->search(fn (Unit $candidate) => $candidate->id === $unit->id);

        $neighborPosition = $request->input('direction') === 'up' ? $position - 1 : $position + 1;

        if ($neighborPosition < 0 || $neighborPosition >= $units->count()) {
            return redirect()->route('units.index')->with('success', 'Gruppe bereits an dieser Position.');
        }

        $neighbor = $units->get($neighborPosition);

        $unitSortOrder = $unit->sort_order;
        $neighborSortOrder = $neighbor->sort_order;

        $unit->update(['sort_order' => $neighborSortOrder]);
        $neighbor->update(['sort_order' => $unitSortOrder]);

        return redirect()->route('units.index')->with('success', 'Reihenfolge aktualisiert.');
    }
}
