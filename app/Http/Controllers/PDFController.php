<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;

class PDFController extends Controller
{
    public function index()
    {
        $pdf = Pdf::loadView('pdf');

        return $pdf->stream('packliste.pdf');
    }
}
