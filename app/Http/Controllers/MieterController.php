<?php

namespace App\Http\Controllers;

use App\Http\Requests\MieterRequest;
use App\Models\Mieter;

class MieterController extends Controller
{
    public function index()
    {
        $mieter = Mieter::orderBy('bezeichnung')->get();

        return view('mieter.index', compact('mieter'));
    }

    public function create()
    {
        return view('mieter.create');
    }

    public function store(MieterRequest $request)
    {
        $mieter = Mieter::create($request->validated());

        if ($request->wantsJson()) {
            return response()->json($mieter);
        }

        return redirect()->route('mieter.index');
    }

    public function show(Mieter $mieter)
    {
        return view('mieter.show', compact('mieter'));
    }

    public function edit(Mieter $mieter)
    {
        return view('mieter.edit', compact('mieter'));
    }

    public function update(MieterRequest $request, Mieter $mieter)
    {
        $mieter->update($request->validated());

        return redirect()->route('mieter.index');
    }

    public function destroy(Mieter $mieter)
    {
        $mieter->delete();

        return redirect()->route('mieter.index');
    }
}
