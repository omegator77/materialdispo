<?php

namespace App\Services;

use App\Models\CameraConfig;
use App\Models\Item;
use App\Models\Production;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CameraConfigService
{
    public function __construct(private ItemAvailabilityService $availability) {}

    /**
     * Legt eine neue Kamera-Konfiguration an.
     * Gibt ['success' => bool, 'error' => string|null, 'config' => CameraConfig|null] zurück.
     */
    public function create(Production $production, array $validated): array
    {
        return DB::transaction(fn () => $this->createLocked($production, $validated));
    }

    private function createLocked(Production $production, array $validated): array
    {
        $this->availability->lockItems($this->slotItemIds($validated));

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
        return DB::transaction(fn () => $this->updateLocked($config, $validated));
    }

    private function updateLocked(CameraConfig $config, array $validated): array
    {
        $this->availability->lockItems($this->slotItemIds($validated));

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
        foreach ($this->slotItemIds($validated) as $itemId) {
            $item = Item::findOrFail($itemId);

            // Ein Gerät kann physisch nur in einem Kamerazug stecken. check() sperrt nur
            // andere Produktionen, daher hier die Züge derselben Produktion prüfen.
            $siblings = $production->cameraConfigs()
                ->when($ignoreConfig, fn ($q) => $q->where('id', '!=', $ignoreConfig->id));
            $this->availability->applyItemInConfigFilter($siblings, $item->id);
            $sibling = $siblings->first();

            if ($sibling) {
                return $item->bezeichnung.': bereits in '.$sibling->cam_number.' dieser Produktion';
            }

            $availability = $this->availability->check($item, $production, $ignoreConfig);

            if (! $availability['available']) {
                return $item->bezeichnung.': '.$availability['reason'];
            }
        }

        return null;
    }

    /**
     * @return Collection<int, mixed>
     */
    private function slotItemIds(array $validated)
    {
        return collect([
            $validated['camera'],
            $validated['lens'] ?? null,
            $validated['tripod'] ?? null,
            $validated['tripod_head'] ?? null,
            $validated['large_lens_adapter'] ?? null,
        ])
            ->filter()
            ->unique();
    }
}
