<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Mietvorgang;
use App\Models\Production;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();

        return view('dashboard', [
            'itemsCount' => Item::count(),
            'rentedItemsCount' => Item::whereNotNull('suppliers_id')->count(),

            'lastActivities' => Activity::with('causer')->latest()->limit(5)->get(),

            'activeProductionsCount' => Production::whereDate('booking_start', '<=', $today)
                ->whereDate('booking_end', '>=', $today)
                ->count(),

            'todayBookedItemsCount' => Item::whereHas('productions', function ($query) use ($today) {
                $query->whereDate('booking_start', '<=', $today)
                    ->whereDate('booking_end', '>=', $today);
            })->count(),

            'runningProductions' => Production::with($this->packStatusRelations())
                ->whereDate('booking_start', '<=', $today)
                ->whereDate('booking_end', '>=', $today)
                ->orderBy('booking_end')
                ->limit(5)
                ->get(),

            'upcomingProductions' => Production::with($this->packStatusRelations())
                ->whereDate('booking_start', '>', $today)
                ->orderBy('booking_start')
                ->limit(5)
                ->get(),

            'latestProductions' => Production::latest()
                ->limit(5)
                ->get(),

            'upcomingTransportEvents' => $this->upcomingTransportEvents($today),
        ]);
    }

    /**
     * Anstehende, noch nicht als "geklärt" markierte Termine der nächsten
     * $days Tage für die Dashboard-Übersicht — Mietvorgänge (Mietbeginn/-ende)
     * und Dry-Hire-Produktionen (Übergabe/Rückgabe) chronologisch gemischt.
     */
    private function upcomingTransportEvents(Carbon $today, int $days = 14): Collection
    {
        $window = $today->copy()->addDays($days);

        $mietvorgangEvents = Mietvorgang::with(['supplier', 'items'])
            ->whereHas('items')
            ->where(function ($q) use ($today, $window) {
                $q->whereBetween('rent_start', [$today, $window])
                    ->orWhereBetween('rent_end', [$today, $window]);
            })
            ->get()
            ->flatMap(function (Mietvorgang $mv) use ($today, $window) {
                $entries = collect();

                $start = Carbon::parse($mv->rent_start);
                $end = Carbon::parse($mv->rent_end);

                if (! $mv->isTransportConfirmed('start') && $start->gte($today) && $start->lte($window)) {
                    $entries->push(['kind' => 'mietvorgang', 'mietvorgang' => $mv, 'type' => 'start', 'date' => $start]);
                }

                if (! $mv->isTransportConfirmed('end') && $end->gte($today) && $end->lte($window)) {
                    $entries->push(['kind' => 'mietvorgang', 'mietvorgang' => $mv, 'type' => 'end', 'date' => $end]);
                }

                return $entries;
            });

        $dryHireEvents = Production::where('is_dry_hire', true)
            ->whereHas('dryHire')
            ->with('dryHire')
            ->where(function ($q) use ($today, $window) {
                $q->whereBetween('booking_start', [$today, $window])
                    ->orWhereBetween('booking_end', [$today, $window]);
            })
            ->get()
            ->flatMap(function (Production $production) use ($today, $window) {
                $entries = collect();
                $dryHire = $production->dryHire;

                $start = Carbon::parse($production->booking_start);
                $end = Carbon::parse($production->booking_end);

                if (! $dryHire->isTransportConfirmed('start') && $start->gte($today) && $start->lte($window)) {
                    $entries->push(['kind' => 'dry_hire', 'production' => $production, 'type' => 'start', 'date' => $start]);
                }

                if (! $dryHire->isTransportConfirmed('end') && $end->gte($today) && $end->lte($window)) {
                    $entries->push(['kind' => 'dry_hire', 'production' => $production, 'type' => 'end', 'date' => $end]);
                }

                return $entries;
            });

        return $mietvorgangEvents->concat($dryHireEvents)->sortBy('date')->values();
    }

    /**
     * Relationen, die Production::packlistEntries()/packedItemIds() braucht,
     * damit der Pack-Status-Badge auf dem Dashboard ohne N+1-Queries auskommt.
     */
    private function packStatusRelations(): array
    {
        return [
            'items.unit',
            'cameraConfigs.item.unit',
            'cameraConfigs.lensItem',
            'cameraConfigs.tripodItem',
            'cameraConfigs.headItem',
            'cameraConfigs.adapterItem',
            'itemPacks',
        ];
    }
}
