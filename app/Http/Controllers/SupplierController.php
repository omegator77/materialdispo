<?php

namespace App\Http\Controllers;

use App\Http\Requests\SupplierRequest;
use App\Models\Supplier;
use Illuminate\Database\QueryException;

class SupplierController extends Controller
{
    public function index()
    {
        $suppliers = Supplier::orderBy('bezeichnung')->get();

        return view('suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        return view('suppliers.create');
    }

    public function store(SupplierRequest $request)
    {
        $supplier = Supplier::create($request->validated());

        if ($request->wantsJson()) {
            return response()->json($supplier);
        }

        return redirect()->route('suppliers.index');
    }

    public function show(Supplier $supplier)
    {
        return view('suppliers.show', compact('supplier'));
    }

    public function edit(Supplier $supplier)
    {
        return view('suppliers.edit', compact('supplier'));
    }

    public function update(SupplierRequest $request, Supplier $supplier)
    {
        $supplier->update($request->validated());

        return redirect()->route('suppliers.index');
    }

    public function destroy(Supplier $supplier)
    {
        // items.suppliers_id und mietvorgaenge.suppliers_id sind RESTRICT: ein
        // Vermieter mit zugeordneten Geräten oder Mietvorgängen lässt sich nicht
        // löschen. Vorher machte das gelöschte Geräte zu „Eigentum" bzw. löschte
        // Mietvorgänge lautlos mit.
        try {
            $supplier->delete();
        } catch (QueryException $e) {
            return redirect()
                ->route('suppliers.index')
                ->with('error', 'Diesem Vermieter sind noch Geräte oder Mietvorgänge zugeordnet — er kann nicht gelöscht werden.');
        }

        return redirect()->route('suppliers.index')->with('success', 'Vermieter gelöscht.');
    }
}
