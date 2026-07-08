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

            'activeVorgaengeCount' => Production::whereDate('booking_start', '<=', $today)
                ->whereDate('booking_end', '>=', $today)
                ->count()
                + Mietvorgang::whereHas('items')
                    ->whereDate('rent_start', '<=', $today)
                    ->whereDate('rent_end', '>=', $today)
                    ->count()
                + Vermietvorgang::whereHas('items')
                    ->whereDate('rent_start', '<=', $today)
                    ->whereDate('rent_end', '>=', $today)
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

            'openVorgaenge' => $this->openVorgaenge(),
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
     * Miet-/Vermietvorgänge (mit Items), bei denen noch nicht alle 4 Häkchen
     * (Transport-Hinweg/-Rückweg + die beiden Material-Status-Häkchen) gesetzt
     * sind — für die "Offene Vorgänge"-Kachel. Kein Zeitfenster: ein Vorgang
     * bleibt sichtbar, bis er komplett abgehakt ist.
     */
    private function openVorgaenge(int $limit = 10): Collection
    {
        $mietvorgaenge = Mietvorgang::with(['supplier', 'items'])
            ->whereHas('items')
            ->get()
            ->map(function (Mietvorgang $mv) {
                // Chronologische Reihenfolge: Angenommen -> Geprüft -> Zur
                // Rückgabe fertig -> Übergeben (unabhängig von den internen
                // Feldnamen kontrolliert/bereit_zur_rueckgabe).
                $checks = [
                    [
                        'label' => $mv->transportActionLabel('start'),
                        'done' => $mv->isTransportConfirmed('start'),
                        'confirmRoute' => route('mietvorgaenge.confirmTransport', [$mv, 'start']),
                        'reopenRoute' => route('mietvorgaenge.reopenTransport', [$mv, 'start']),
                    ],
                    [
                        'label' => 'Geprüft',
                        'done' => $mv->isKontrolliert(),
                        'confirmRoute' => route('mietvorgaenge.confirmKontrolliert', $mv),
                        'reopenRoute' => route('mietvorgaenge.reopenKontrolliert', $mv),
                    ],
                    [
                        'label' => 'Zur Rückgabe fertig',
                        'done' => $mv->isBereitZurRueckgabe(),
                        'confirmRoute' => route('mietvorgaenge.confirmBereitZurRueckgabe', $mv),
                        'reopenRoute' => route('mietvorgaenge.reopenBereitZurRueckgabe', $mv),
                    ],
                    [
                        'label' => $mv->transportActionLabel('end'),
                        'done' => $mv->isTransportConfirmed('end'),
                        'confirmRoute' => route('mietvorgaenge.confirmTransport', [$mv, 'end']),
                        'reopenRoute' => route('mietvorgaenge.reopenTransport', [$mv, 'end']),
                    ],
                ];

                return [
                    'kind' => 'mietvorgang',
                    'model' => $mv,
                    'title' => $mv->supplier?->bezeichnung ?? 'Vermieter gelöscht',
                    'badge' => 'Miete',
                    'badgeClass' => 'bg-amber-50 text-amber-700',
                    'showRoute' => route('mietvorgaenge.show', $mv),
                    'checks' => $checks,
                    'doneCount' => collect($checks)->where('done', true)->count(),
                ];
            })
            ->filter(fn (array $e) => $e['doneCount'] < 4);

        $vermietvorgaenge = Vermietvorgang::with(['mieter', 'items'])
            ->whereHas('items')
            ->get()
            ->map(function (Vermietvorgang $vv) {
                // Chronologische Reihenfolge: Gerichtet -> Übergeben ->
                // Angenommen -> Geprüft (unabhängig vom internen Feldnamen
                // vollstaendig_zurueck).
                $checks = [
                    [
                        'label' => 'Gerichtet',
                        'done' => $vv->isGerichtet(),
                        'confirmRoute' => route('vermietvorgaenge.confirmGerichtet', $vv),
                        'reopenRoute' => route('vermietvorgaenge.reopenGerichtet', $vv),
                    ],
                    [
                        'label' => $vv->transportActionLabel('start'),
                        'done' => $vv->isTransportConfirmed('start'),
                        'confirmRoute' => route('vermietvorgaenge.confirmTransport', [$vv, 'start']),
                        'reopenRoute' => route('vermietvorgaenge.reopenTransport', [$vv, 'start']),
                    ],
                    [
                        'label' => $vv->transportActionLabel('end'),
                        'done' => $vv->isTransportConfirmed('end'),
                        'confirmRoute' => route('vermietvorgaenge.confirmTransport', [$vv, 'end']),
                        'reopenRoute' => route('vermietvorgaenge.reopenTransport', [$vv, 'end']),
                    ],
                    [
                        'label' => 'Geprüft',
                        'done' => $vv->isVollstaendigZurueck(),
                        'confirmRoute' => route('vermietvorgaenge.confirmVollstaendigZurueck', $vv),
                        'reopenRoute' => route('vermietvorgaenge.reopenVollstaendigZurueck', $vv),
                    ],
                ];

                return [
                    'kind' => 'vermietvorgang',
                    'model' => $vv,
                    'title' => $vv->mieter?->bezeichnung ?? 'Mieter gelöscht',
                    'badge' => 'Verleih',
                    'badgeClass' => 'bg-purple-50 text-purple-700',
                    'showRoute' => route('vermietvorgaenge.show', $vv),
                    'checks' => $checks,
                    'doneCount' => collect($checks)->where('done', true)->count(),
                ];
            })
            ->filter(fn (array $e) => $e['doneCount'] < 4);

        return $mietvorgaenge->concat($vermietvorgaenge)
            ->sortBy(fn (array $e) => $e['model']->rent_start)
            ->take($limit)
            ->values();
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
