<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Mietvorgang;
use App\Models\Production;
use App\Models\Vermietvorgang;
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

            'todayBookedItemsCount' => Item::where(function ($query) use ($today) {
                $query->whereHas('productions', function ($q) use ($today) {
                    $q->whereDate('booking_start', '<=', $today)
                        ->whereDate('booking_end', '>=', $today);
                })
                ->orWhere(function ($q) use ($today) {
                    $q->whereNotNull('mietvorgang_id')
                        ->whereDate('rent_start', '<=', $today)
                        ->whereDate('rent_end', '>=', $today);
                })
                ->orWhere(function ($q) use ($today) {
                    $q->whereNotNull('vermietvorgang_id')
                        ->whereDate('verleih_start', '<=', $today)
                        ->whereDate('verleih_end', '>=', $today);
                });
            })->count(),

            'runningEntries' => $this->runningEntries($today),
            'upcomingEntries' => $this->upcomingEntries($today),

            'latestProductions' => Production::latest()
                ->limit(5)
                ->get(),

            'upcomingTransportEvents' => $this->upcomingTransportEvents($today),
        ]);
    }

    /**
     * Heute laufende Produktionen, Mietvorgänge und Vermietvorgänge (Geräte
     * gerade beim Vermieter/Kunden bzw. Produktion im Gange), chronologisch
     * nach Enddatum gemischt für die "Laufende Produktionen"-Kachel.
     */
    private function runningEntries(Carbon $today, int $limit = 5): Collection
    {
        $productions = Production::with($this->packStatusRelations())
            ->whereDate('booking_start', '<=', $today)
            ->whereDate('booking_end', '>=', $today)
            ->get()
            ->map(fn (Production $p) => ['kind' => 'production', 'model' => $p, 'sort' => Carbon::parse($p->booking_end)]);

        $mietvorgaenge = Mietvorgang::with(['supplier', 'items'])
            ->whereHas('items')
            ->whereDate('rent_start', '<=', $today)
            ->whereDate('rent_end', '>=', $today)
            ->get()
            ->map(fn (Mietvorgang $mv) => ['kind' => 'mietvorgang', 'model' => $mv, 'sort' => Carbon::parse($mv->rent_end)]);

        $vermietvorgaenge = Vermietvorgang::with(['mieter', 'items'])
            ->whereHas('items')
            ->whereDate('rent_start', '<=', $today)
            ->whereDate('rent_end', '>=', $today)
            ->get()
            ->map(fn (Vermietvorgang $vv) => ['kind' => 'vermietvorgang', 'model' => $vv, 'sort' => Carbon::parse($vv->rent_end)]);

        return $productions->concat($mietvorgaenge)->concat($vermietvorgaenge)
            ->sortBy('sort')
            ->take($limit)
            ->values();
    }

    /**
     * Anstehende Produktionen, Mietvorgänge und Vermietvorgänge (noch nicht
     * begonnen), chronologisch nach Startdatum gemischt für die "Nächste
     * Produktionen"-Kachel.
     */
    private function upcomingEntries(Carbon $today, int $limit = 5): Collection
    {
        $productions = Production::with($this->packStatusRelations())
            ->whereDate('booking_start', '>', $today)
            ->get()
            ->map(fn (Production $p) => ['kind' => 'production', 'model' => $p, 'sort' => Carbon::parse($p->booking_start)]);

        $mietvorgaenge = Mietvorgang::with(['supplier', 'items'])
            ->whereHas('items')
            ->whereDate('rent_start', '>', $today)
            ->get()
            ->map(fn (Mietvorgang $mv) => ['kind' => 'mietvorgang', 'model' => $mv, 'sort' => Carbon::parse($mv->rent_start)]);

        $vermietvorgaenge = Vermietvorgang::with(['mieter', 'items'])
            ->whereHas('items')
            ->whereDate('rent_start', '>', $today)
            ->get()
            ->map(fn (Vermietvorgang $vv) => ['kind' => 'vermietvorgang', 'model' => $vv, 'sort' => Carbon::parse($vv->rent_start)]);

        return $productions->concat($mietvorgaenge)->concat($vermietvorgaenge)
            ->sortBy('sort')
            ->take($limit)
            ->values();
    }

    /**
     * Anstehende, noch nicht als "geklärt" markierte Termine der nächsten
     * $days Tage für die Dashboard-Übersicht — Mietvorgänge und Vermietvorgänge
     * (Mietbeginn/-ende bzw. Verleihbeginn/-ende) chronologisch gemischt.
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

        $vermietvorgangEvents = Vermietvorgang::with(['mieter', 'items'])
            ->whereHas('items')
            ->where(function ($q) use ($today, $window) {
                $q->whereBetween('rent_start', [$today, $window])
                    ->orWhereBetween('rent_end', [$today, $window]);
            })
            ->get()
            ->flatMap(function (Vermietvorgang $vv) use ($today, $window) {
                $entries = collect();

                $start = Carbon::parse($vv->rent_start);
                $end = Carbon::parse($vv->rent_end);

                if (! $vv->isTransportConfirmed('start') && $start->gte($today) && $start->lte($window)) {
                    $entries->push(['kind' => 'vermietvorgang', 'vermietvorgang' => $vv, 'type' => 'start', 'date' => $start]);
                }

                if (! $vv->isTransportConfirmed('end') && $end->gte($today) && $end->lte($window)) {
                    $entries->push(['kind' => 'vermietvorgang', 'vermietvorgang' => $vv, 'type' => 'end', 'date' => $end]);
                }

                return $entries;
            });

        return $mietvorgangEvents->concat($vermietvorgangEvents)->sortBy('date')->values();
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
