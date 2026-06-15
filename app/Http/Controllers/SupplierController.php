<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Supplier;

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

    public function store(Request $request)
    {
        $request->validate([
            'bezeichnung' => 'required',
            'kontakt' => 'nullable',
            'phone' => 'nullable',
            'email' => 'nullable|email',
        ]);

        Supplier::create($request->only([
            'bezeichnung',
            'kontakt',
            'phone',
            'email',
        ]));

        return redirect()->route('suppliers.index');
    }

    public function show(string $id)
    {
        $supplier = Supplier::findOrFail($id);

        return view('suppliers.show', compact('supplier'));
    }

    public function edit(string $id)
    {
        $supplier = Supplier::findOrFail($id);

        return view('suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, string $id)
    {
        $supplier = Supplier::findOrFail($id);

        $request->validate([
            'bezeichnung' => 'required',
            'kontakt' => 'nullable',
            'phone' => 'nullable',
            'email' => 'nullable|email',
        ]);

        $supplier->update($request->only([
            'bezeichnung',
            'kontakt',
            'phone',
            'email',
        ]));

        return redirect()->route('suppliers.index');
    }

    public function destroy(string $id)
    {
        $supplier = Supplier::findOrFail($id);

        $supplier->delete();

        return redirect()->route('suppliers.index');
    }
}