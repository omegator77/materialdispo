<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Production;
use Carbon\Carbon;
use Spatie\Activitylog\Models\Activity;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();

        return view('dashboard', [
            'itemsCount' => Item::count(),

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
        ]);
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
