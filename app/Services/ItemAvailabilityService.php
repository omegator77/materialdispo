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
     *
     * $ignoreConfig blendet eine Kamerakonfiguration beim Konfliktcheck aus —
     * nötig beim Editieren einer bestehenden Config, damit sie nicht mit sich
     * selbst kollidiert.
     */
    public function check(Item $item, Production $production, ?CameraConfig $ignoreConfig = null): array
    {
        // 1. Mietfenster: Mietzeitraum muss Produktionszeitraum abdecken
        if ($item->suppliers_id) {
            if ($item->rent_start && $item->rent_start > $production->booking_start) {
                return [
                    'available' => false,
                    'reason' => 'Mietbeginn zu spät',
                ];
            }

            if ($item->rent_end && $item->rent_end < $production->booking_end) {
                return [
                    'available' => false,
                    'reason' => 'Mietende zu früh',
                ];
            }
        }

        // 2. Verleihfenster: Gerät ist während eines Verleihzeitraums (Vermietung an
        //    einen Kunden) für Produktionen nicht verfügbar, sofern sich der
        //    Zeitraum mit der Produktion überschneidet.
        if ($item->mieter_id && $item->verleih_start && $item->verleih_end) {
            if ($item->verleih_start <= $production->booking_end && $item->verleih_end >= $production->booking_start) {
                return [
                    'available' => false,
                    'reason' => 'Vermietet an '.($item->mieter->bezeichnung ?? 'Mieter gelöscht'),
                ];
            }
        }

        // 3. Produktionskonflikt: Gerät bereits in überlappender Produktion gebucht
        $conflict = $item->productions()
            ->where('productions.id', '!=', $production->id)
            ->where('booking_start', '<=', $production->booking_end)
            ->where('booking_end', '>=', $production->booking_start)
            ->first();

        if ($conflict) {
            return [
                'available' => false,
                'reason' => 'Gebucht in Produktion: '.$conflict->bezeichnung,
            ];
        }

        // 4. Kamerakonfig-Konflikt: Gerät in Kamerazug einer überlappenden Produktion
        $configConflict = CameraConfig::query()
            ->where('production_id', '!=', $production->id)
            ->whereHas('production', function ($q) use ($production) {
                $q->where('booking_start', '<=', $production->booking_end)
                    ->where('booking_end', '>=', $production->booking_start);
            });

        if ($ignoreConfig) {
            $configConflict->where('id', '!=', $ignoreConfig->id);
        }

        $this->applyItemInConfigFilter($configConflict, $item->id);

        $configConflict = $configConflict->with('production')->first();

        if ($configConflict) {
            return [
                'available' => false,
                'reason' => 'In Kamerakonfiguration: '.$configConflict->production->bezeichnung,
            ];
        }

        return [
            'available' => true,
            'reason' => null,
        ];
    }

    /**
     * Prüft ob ein Gerät für einen Verleihzeitraum (Vermietung an einen Kunden)
     * verfügbar ist — d.h. nicht bereits während dieses Zeitraums einer
     * Produktion oder Kamerakonfiguration zugeordnet ist.
     * Gibt ['available' => bool, 'reason' => string|null] zurück.
     */
    public function checkForVerleih(Item $item, string $verleihStart, string $verleihEnd): array
    {
        $conflict = $item->productions()
            ->where('booking_start', '<=', $verleihEnd)
            ->where('booking_end', '>=', $verleihStart)
            ->first();

        if ($conflict) {
            return [
                'available' => false,
                'reason' => 'Gebucht in Produktion: '.$conflict->bezeichnung,
            ];
        }

        $configConflict = CameraConfig::query()
            ->whereHas('production', function ($q) use ($verleihStart, $verleihEnd) {
                $q->where('booking_start', '<=', $verleihEnd)
                    ->where('booking_end', '>=', $verleihStart);
            });

        $this->applyItemInConfigFilter($configConflict, $item->id);

        $configConflict = $configConflict->with('production')->first();

        if ($configConflict) {
            return [
                'available' => false,
                'reason' => 'In Kamerakonfiguration: '.$configConflict->production->bezeichnung,
            ];
        }

        return [
            'available' => true,
            'reason' => null,
        ];
    }

    /**
     * Geräte, die einer Produktion noch zugeordnet werden können: weder als
     * Einzelgerät noch in einem Kamerazug-Slot bereits enthalten. Markiert
     * jedes Gerät mit is_available/availability_reason und blendet nicht
     * verfügbare Geräte aus, sofern $showUnavailable nicht gesetzt ist.
     */
    public function assignableItemsFor(Production $production, ?string $unitFilter, bool $showUnavailable): \Illuminate\Support\Collection
    {
        $itemsQuery = Item::query()
            ->whereDoesntHave('productions', function ($query) use ($production) {
                $query->where('productions.id', $production->id);
            })
            ->where(function ($query) use ($production) {
                $query
                    ->whereDoesntHave('cameraConfigs', function ($q) use ($production) {
                        $q->where('production_id', $production->id);
                    })
                    ->whereNotIn('id', function ($q) use ($production) {
                        $q->select('lens')
                            ->from('camera_configs')
                            ->where('production_id', $production->id)
                            ->whereNotNull('lens');
                    })
                    ->whereNotIn('id', function ($q) use ($production) {
                        $q->select('tripod')
                            ->from('camera_configs')
                            ->where('production_id', $production->id)
                            ->whereNotNull('tripod');
                    })
                    ->whereNotIn('id', function ($q) use ($production) {
                        $q->select('tripod_head')
                            ->from('camera_configs')
                            ->where('production_id', $production->id)
                            ->whereNotNull('tripod_head');
                    })
                    ->whereNotIn('id', function ($q) use ($production) {
                        $q->select('large_lens_adapter')
                            ->from('camera_configs')
                            ->where('production_id', $production->id)
                            ->whereNotNull('large_lens_adapter');
                    });
            });

        if ($unitFilter) {
            $itemsQuery->where('units_id', $unitFilter);
        }

        $items = $itemsQuery
            ->orderBy('bezeichnung')
            ->get()
            ->map(function ($item) use ($production) {
                $check = $this->check($item, $production);

                $item->is_available = $check['available'];
                $item->availability_reason = $check['reason'];

                return $item;
            });

        if (! $showUnavailable) {
            $items = $items->filter(fn ($item) => $item->is_available)->values();
        }

        return $items;
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
