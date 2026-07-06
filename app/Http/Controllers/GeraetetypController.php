<?php

namespace App\Http\Controllers;

use App\Http\Requests\GeraetetypRequest;
use App\Models\Geraetetyp;
use App\Models\Unit;
use Illuminate\Database\QueryException;

class GeraetetypController extends Controller
{
    public function index()
    {
        $geraetetypen = Geraetetyp::with('unit')->withCount('items')->orderBy('units_id')->orderBy('bezeichnung')->get();
        $units = Unit::orderBy('bezeichnung')->get();

        return view('geraetetypen.index', compact('geraetetypen', 'units'));
    }

    public function create()
    {
        $units = Unit::orderBy('bezeichnung')->get();

        return view('geraetetypen.create', compact('units'));
    }

    public function store(GeraetetypRequest $request)
    {
        Geraetetyp::create($request->validated());

        return redirect()->route('geraetetypen.index')->with('success', 'Gerätetyp angelegt.');
    }

    public function edit(Geraetetyp $geraetetyp)
    {
        $units = Unit::orderBy('bezeichnung')->get();

        return view('geraetetypen.edit', compact('geraetetyp', 'units'));
    }

    public function update(GeraetetypRequest $request, Geraetetyp $geraetetyp)
    {
        $geraetetyp->update($request->validated());

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
