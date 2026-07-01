<?php

namespace App\Http\Controllers;

use App\Models\Booking;

class BookingController extends Controller
{
    public function index()
    {
        $bookings = Booking::all();

        return view('bookings.index', ['bookings' => $bookings]);
    }
}
