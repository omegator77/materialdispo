<?php

namespace App\Http\Controllers;

use App\Models\Geraetetyp;
use App\Models\Unit;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class GeraetetypController extends Controller
{
    public function index()
    {
        $geraetetypen = Geraetetyp::with('unit')->withCount('items')->orderBy('units_id')->orderBy('bezeichnung')->get();

        return view('geraetetypen.index', compact('geraetetypen'));
    }

    public function create()
    {
        $units = Unit::orderBy('bezeichnung')->get();

        return view('geraetetypen.create', compact('units'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'units_id' => ['required', 'exists:units,id'],
            'bezeichnung' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        Geraetetyp::create($validated);

        return redirect()->route('geraetetypen.index')->with('success', 'Gerätetyp angelegt.');
    }

    public function edit(Geraetetyp $geraetetyp)
    {
        $units = Unit::orderBy('bezeichnung')->get();

        return view('geraetetypen.edit', compact('geraetetyp', 'units'));
    }

    public function update(Request $request, Geraetetyp $geraetetyp)
    {
        $validated = $request->validate([
            'units_id' => ['required', 'exists:units,id'],
            'bezeichnung' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $geraetetyp->update($validated);

        return redirect()->route('geraetetypen.index')->with('success', 'Gerätetyp aktualisiert.');
    }

    public function destroy(Geraetetyp $geraetetyp)
    {
        try {
            $geraetetyp->delete();
        } catch (QueryException $e) {
            return redirect()
                ->route('geraetetypen.index')
                ->with('error', 'Dieser Gerätetyp ist noch Geräten zugeordnet und kann nicht gelöscht werden.');
        }

        return redirect()->route('geraetetypen.index')->with('success', 'Gerätetyp gelöscht.');
    }
}
