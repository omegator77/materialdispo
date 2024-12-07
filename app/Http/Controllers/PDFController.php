<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
//use Pdf;
use Barryvdh\DomPDF\Facade\PDF;

class PDFController extends Controller
{
    public function index () 
    {
       $pdf = Pdf::loadView('pdf');

       //return $pdf->download('iitit.pdf');
       return $pdf->stream('packliste.pdf');
    }
}
