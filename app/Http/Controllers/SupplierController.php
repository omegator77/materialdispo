<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Supplier;

class SupplierController extends Controller
{
    public function index(){
        $suppliers = Supplier::all();
        //dd($suppliers);
        return view('suppliers.index', ['suppliers'=> $suppliers]);
    }
}
