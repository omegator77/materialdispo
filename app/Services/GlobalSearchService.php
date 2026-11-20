<?php

namespace App\Services;

use App\Models\Item;
use App\Models\Mieter;
use App\Models\Production;
use App\Models\Supplier;
use App\Models\Unit;
use Carbon\Carbon;

/**
 * Backend der Command-Palette (Strg+K): ein Aufruf, alle Objekttypen.
 * Gerätetreffer tragen gleich ihren aktuellen Status (frei / nächste Buchung).
 */
class GlobalSearchService
{
    /**
     * @return array<int, array{type: string, title: string, subtitle: ?string, url: string, badge: ?string}>
     */
    public function search(string $query, int $limit = 6): array
    {
        $query = trim($query);
        if (mb_strlen($query) < 1) {
            return [];
        }

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $query).'%';
        $results = [];
        $today = Carbon::today()->toDateString();

        // Geräte: Bezeichnung, Nummer oder "Bezeichnung Nummer" ("ldx 22").
        $tokens = preg_split('/\s+/', $query) ?: [];
        $items = Item::with(['unit', 'productions' => fn ($q) => $q->where('booking_end', '>=', $today)->orderBy('booking_start')])
            ->where(function ($q) use ($tokens) {
                foreach ($tokens as $t) {
                    $tl = '%'.str_replace(['%', '_'], ['\%', '\_'], $t).'%';
                    $q->where(fn ($qq) => $qq->where('bezeichnung', 'like', $tl)->orWhere('nummer', 'like', $tl)->orWhere('description', 'like', $tl));
                }
            })
            ->orderBy('bezeichnung')->orderBy('nummer')
            ->limit($limit)->get();

        foreach ($items as $item) {
            $next = $item->productions->first();
            $busyNow = $next && $next->booking_start <= $today;
            $results[] = [
                'type' => 'Gerät',
                'title' => $item->bezeichnung.($item->nummer ? " · Nr. {$item->nummer}" : ''),
                'subtitle' => ($item->unit?->bezeichnung ?? 'Ohne Gruppe').($item->suppliers_id ? ' · Miete' : ''),
                'url' => route('items.show', $item->id),
                'badge' => $next
                    ? ($busyNow ? 'in Produktion: ' : 'gebucht ab '.Carbon::parse($next->booking_start)->format('d.m.').': ').$next->bezeichnung
                    : 'frei',
                'free' => $next === null,
            ];
        }

        foreach (Production::where('bezeichnung', 'like', $like)->orderByDesc('booking_start')->limit($limit)->get() as $p) {
            $results[] = [
                'type' => 'Produktion',
                'title' => $p->bezeichnung,
                'subtitle' => Carbon::parse($p->booking_start)->format('d.m.Y').' – '.Carbon::parse($p->booking_end)->format('d.m.Y'),
                'url' => route('productions.show', $p),
                'badge' => null,
            ];
        }

        foreach (Supplier::where('bezeichnung', 'like', $like)->limit(3)->get() as $s) {
            $results[] = ['type' => 'Vermieter', 'title' => $s->bezeichnung, 'subtitle' => null, 'url' => route('suppliers.show', $s), 'badge' => null];
        }
        foreach (Mieter::where('bezeichnung', 'like', $like)->limit(3)->get() as $m) {
            $results[] = ['type' => 'Mieter', 'title' => $m->bezeichnung, 'subtitle' => null, 'url' => route('mieter.show', $m), 'badge' => null];
        }

        // Gruppen → gefilterte Geräteliste.
        foreach (Unit::where('bezeichnung', 'like', $like)->limit(3)->get() as $u) {
            $results[] = ['type' => 'Gruppe', 'title' => $u->bezeichnung, 'subtitle' => 'Geräteliste filtern', 'url' => route('items.index', ['unit_id' => $u->id]), 'badge' => null];
        }

        return $results;
    }
}
