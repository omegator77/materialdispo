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
        $bookingStatus = $request->input('booking_status', 'all');

        $items = Item::with([
            'unit',
            'supplier',
            'productions' => function ($query) use ($start, $end) {
                $query->whereDate('booking_start', '<=', $end)
                    ->whereDate('booking_end', '>=', $start);
            },
            'mietvorgaenge' => function ($query) use ($start, $end) {
                $query->whereDate('rent_start', '<=', $end)
                    ->whereDate('rent_end', '>=', $start)
                    ->with('supplier');
            },
            'vermietvorgaenge' => function ($query) use ($start, $end) {
                $query->whereDate('rent_start', '<=', $end)
                    ->whereDate('rent_end', '>=', $start)
                    ->with('mieter');
            },
        ])
            ->when($unitId, fn ($query) => $query->where('units_id', $unitId))
            ->orderBy('bezeichnung')
            ->orderBy('nummer')
            ->get();

        if ($bookingStatus !== 'all') {
            $items = $items->filter(function (Item $item) use ($bookingStatus) {
                $isBooked = $item->productions->isNotEmpty()
                    || $item->mietvorgaenge->isNotEmpty()
                    || $item->vermietvorgaenge->isNotEmpty();

                return $bookingStatus === 'booked' ? $isBooked : ! $isBooked;
            })->values();
        }

        $units = Unit::orderBy('sort_order')->orderBy('bezeichnung')->get();

        // Eine Gruppe pro konfigurierter Unit (in sort_order-Reihenfolge) plus
        // eine abschließende "ohne Gruppe"-Gruppe für Items mit units_id ===
        // null; leere Gruppen (z. B. durch den booking_status-Filter) fallen
        // raus, damit die View nicht über leere Abschnitte iterieren muss.
        $itemsByUnit = $items->groupBy('units_id');

        $groupedItems = $units
            ->map(fn (Unit $unit) => ['unit' => $unit, 'items' => $itemsByUnit->get($unit->id, collect())])
            ->push(['unit' => null, 'items' => $itemsByUnit->get(null, collect())])
            ->filter(fn (array $group) => $group['items']->isNotEmpty())
            ->values();

        return view('timeline.items', compact(
            'items',
            'units',
            'start',
            'end',
            'unitId',
            'bookingStatus',
            'groupedItems'
        ));
    }
}
