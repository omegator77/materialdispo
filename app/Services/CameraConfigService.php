<?php

namespace App\Services;

use App\Models\CameraConfig;
use App\Models\Item;
use App\Models\Production;

class CameraConfigService
{
    public function __construct(private ItemAvailabilityService $availability) {}

    /**
     * Legt eine neue Kamera-Konfiguration an.
     * Gibt ['success' => bool, 'error' => string|null, 'config' => CameraConfig|null] zurück.
     */
    public function create(Production $production, array $validated): array
    {
        $conflict = $this->checkSlotAvailability($production, $validated);

        if ($conflict) {
            return ['success' => false, 'error' => $conflict, 'config' => null];
        }

        $config = new CameraConfig;
        $config->production_id = $production->id;
        $config->item_id = $validated['camera'];
        $config->lens = $validated['lens'] ?? null;
        $config->tripod = $validated['tripod'] ?? null;
        $config->tripod_head = $validated['tripod_head'] ?? null;
        $config->large_lens_adapter = $validated['large_lens_adapter'] ?? null;
        $config->cam_number = $validated['cam_number'];
        $config->notes = $validated['notes'] ?? null;
        $config->save();

        return ['success' => true, 'error' => null, 'config' => $config];
    }

    /**
     * Aktualisiert eine bestehende Kamera-Konfiguration.
     * Gibt ['success' => bool, 'error' => string|null, 'config' => CameraConfig|null] zurück.
     */
    public function update(CameraConfig $config, array $validated): array
    {
        $production = Production::findOrFail($config->production_id);

        $conflict = $this->checkSlotAvailability($production, $validated, $config);

        if ($conflict) {
            return ['success' => false, 'error' => $conflict, 'config' => null];
        }

        $config->cam_number = $validated['cam_number'];
        $config->item_id = $validated['camera'];
        $config->lens = $validated['lens'] ?? null;
        $config->tripod = $validated['tripod'] ?? null;
        $config->tripod_head = $validated['tripod_head'] ?? null;
        $config->large_lens_adapter = $validated['large_lens_adapter'] ?? null;
        $config->notes = $validated['notes'] ?? null;
        $config->save();

        return ['success' => true, 'error' => null, 'config' => $config];
    }

    /**
     * Prüft alle ausgewählten Slot-Items auf Verfügbarkeit.
     * Gibt die erste Konfliktmeldung zurück oder null wenn alles verfügbar ist.
     */
    private function checkSlotAvailability(Production $production, array $validated, ?CameraConfig $ignoreConfig = null): ?string
    {
        $selectedItemIds = collect([
            $validated['camera'],
            $validated['lens'] ?? null,
            $validated['tripod'] ?? null,
            $validated['tripod_head'] ?? null,
            $validated['large_lens_adapter'] ?? null,
        ])
            ->filter()
            ->unique();

        foreach ($selectedItemIds as $itemId) {
            $item = Item::findOrFail($itemId);

            $availability = $this->availability->check($item, $production, $ignoreConfig);

            if (! $availability['available']) {
                return $item->bezeichnung.': '.$availability['reason'];
            }
        }

        return null;
    }
}
