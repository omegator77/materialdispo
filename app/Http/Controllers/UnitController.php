<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $units = Unit::all();
       // dd($units);
       return view('units.index', ['units'=> $units]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('units.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'bezeichnung' => 'required',
            
            ]);

        $unit = Unit::create([
            'bezeichnung'=>$request->input('bezeichnung'),
            'description'=>$request->input('description'),
            
        ]);

        return redirect('/units');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Holt die Unit anhand der ID oder gibt eine 404-Fehlermeldung zurück, falls nicht gefunden
    $unit = Unit::findOrFail($id);

    // Gibt die Unit an die View weiter
    return view('units.show', compact('unit'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $unit = Unit::findOrFail($id);
        return view('units.edit', compact('unit'));
        //return redirect('/units');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'bezeichnung' => 'required',
        ]);
    
        $unit = Unit::findOrFail($id);
        $unit->update([
            'bezeichnung' => $request->input('bezeichnung'),
            'description' => $request->input('description'),
        ]);
    
        return redirect('units');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Unit::where('id', $id)->delete();
                return redirect('/units');
    }
}
