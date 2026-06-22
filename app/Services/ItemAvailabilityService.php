<?php

namespace App\Services;

use App\Models\CameraConfig;
use App\Models\Item;
use App\Models\Production;

class ItemAvailabilityService
{
    /**
     * Prüft ob ein Gerät für eine Produktion verfügbar ist.
     * Gibt ['available' => bool, 'reason' => string|null] zurück.
     */
    public function check(Item $item, Production $production): array
    {
        // 1. Mietfenster: Mietzeitraum muss Produktionszeitraum abdecken
        if ($item->suppliers_id) {
            if ($item->rent_start && $item->rent_start > $production->booking_start) {
                return [
                    'available' => false,
                    'reason'    => 'Mietbeginn zu spät',
                ];
            }

            if ($item->rent_end && $item->rent_end < $production->booking_end) {
                return [
                    'available' => false,
                    'reason'    => 'Mietende zu früh',
                ];
            }
        }

        // 2. Produktionskonflikt: Gerät bereits in überlappender Produktion gebucht
        $conflict = $item->productions()
            ->where('productions.id', '!=', $production->id)
            ->where('booking_start', '<=', $production->booking_end)
            ->where('booking_end', '>=', $production->booking_start)
            ->first();

        if ($conflict) {
            return [
                'available' => false,
                'reason'    => 'Gebucht in Produktion: ' . $conflict->bezeichnung,
            ];
        }

        // 3. Kamerakonfig-Konflikt: Gerät in Kamerazug einer überlappenden Produktion
        $configConflict = CameraConfig::query()
            ->where('production_id', '!=', $production->id)
            ->whereHas('production', function ($q) use ($production) {
                $q->where('booking_start', '<=', $production->booking_end)
                    ->where('booking_end', '>=', $production->booking_start);
            });

        $this->applyItemInConfigFilter($configConflict, $item->id);

        $configConflict = $configConflict->with('production')->first();

        if ($configConflict) {
            return [
                'available' => false,
                'reason'    => 'In Kamerakonfiguration: ' . $configConflict->production->bezeichnung,
            ];
        }

        return [
            'available' => true,
            'reason'    => null,
        ];
    }

    /**
     * Alle Spalten in camera_configs die ein Item referenzieren können.
     */
    public function configItemColumns(): array
    {
        return ['item_id', 'lens', 'tripod', 'tripod_head', 'large_lens_adapter'];
    }

    /**
     * Erweitert eine Query so dass nur Configs gefunden werden die das Item enthalten.
     */
    public function applyItemInConfigFilter($query, int $itemId): void
    {
        $query->where(function ($q) use ($itemId) {
            foreach ($this->configItemColumns() as $column) {
                $q->orWhere($column, $itemId);
            }
        });
    }
}
