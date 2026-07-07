<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Unit;
use Illuminate\Http\Request;

class TimelineController extends Controller
{
    public function items(Request $request)
    {
        $start = $request->input('start', now()->startOfMonth()->toDateString());
        $end = $request->input('end', now()->addMonth()->endOfMonth()->toDateString());
        $unitId = $request->input('unit_id');

        $items = Item::with([
            'unit',
            'supplier',
            'mieter',
            'productions' => function ($query) use ($start, $end) {
                $query->whereDate('booking_start', '<=', $end)
                    ->whereDate('booking_end', '>=', $start);
            },
        ])
            ->when($unitId, fn ($query) => $query->where('units_id', $unitId))
            ->orderBy('units_id')
            ->orderBy('nummer')
            ->orderBy('bezeichnung')
            ->get();

        $units = Unit::orderBy('bezeichnung')->get();

        return view('timeline.items', compact(
            'items',
            'units',
            'start',
            'end',
            'unitId'
        ));
    }
}
