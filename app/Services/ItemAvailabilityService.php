<?php

namespace App\Services;

use App\Models\Item;
use App\Models\Production;
use Illuminate\Support\Facades\DB;

class ItemAvailabilityService
{
    public function assertAvailable(Production $production, Item $item): void
    {
        $start = $production->booking_start;
        $end   = $production->booking_end;

        // Miet-Item
        if ($item->is_rented) {
            if (empty($item->rent_start) || empty($item->rent_end)) {
                throw new \RuntimeException("Miet-Item ohne Mietzeitraum.");
            }
            if (!($item->rent_start <= $start && $item->rent_end >= $end)) {
                throw new \RuntimeException("Miet-Item im Zeitraum nicht verfügbar.");
            }
            return;
        }

        // Eigenbestand → Konflikte prüfen
        $conflict = DB::table('item_production')
            ->join('productions', 'productions.id', '=', 'item_production.production_id')
            ->where('item_production.item_id', $item->id)
            ->where('productions.id', '!=', $production->id)
            ->where(function ($q) use ($start, $end) {
                $q->where('productions.booking_start', '<=', $end)
                  ->where('productions.booking_end', '>=', $start);
            })
            ->exists();

        if ($conflict) {
            throw new \RuntimeException("Item ist im Zeitraum bereits anderweitig gebucht.");
        }
    }

    public function bookAll(Production $production, array $itemIds): void
    {
        $itemIds = array_unique(array_filter($itemIds));
        $items   = Item::whereIn('id', $itemIds)->get()->keyBy('id');

        foreach ($itemIds as $id) {
            $item = $items->get($id);
            if (!$item) {
                throw new \RuntimeException("Item #$id existiert nicht.");
            }
            $this->assertAvailable($production, $item);
        }

        DB::transaction(fn() => $production->items()->syncWithoutDetaching($itemIds));
    }
}
