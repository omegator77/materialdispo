<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Production;
use App\Models\Supplier;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();

        return view('dashboard', [
            'itemsCount' => Item::count(),
            'suppliersCount' => Supplier::count(),

            'activeProductionsCount' => Production::whereDate('booking_start', '<=', $today)
                ->whereDate('booking_end', '>=', $today)
                ->count(),

            'todayBookedItemsCount' => Item::whereHas('productions', function ($query) use ($today) {
                $query->whereDate('booking_start', '<=', $today)
                    ->whereDate('booking_end', '>=', $today);
            })->count(),

            'runningProductions' => Production::whereDate('booking_start', '<=', $today)
                ->whereDate('booking_end', '>=', $today)
                ->orderBy('booking_end')
                ->limit(5)
                ->get(),

            'upcomingProductions' => Production::whereDate('booking_start', '>', $today)
                ->orderBy('booking_start')
                ->limit(5)
                ->get(),

            'latestProductions' => Production::latest()
                ->limit(5)
                ->get(),
        ]);
    }
}