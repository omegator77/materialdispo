<?php

namespace App\Services;

use App\Models\CameraConfig;
use App\Models\Geraetetyp;
use App\Models\Item;
use App\Models\Production;

/**
 * Kamerazug-Assistent: lernt aus früheren Kamerakonfigurationen, welche
 * Objektiv-/Stativ-/Kopf-/Adapter-Typen zu einer Kamera gehören, und legt
 * mehrere Kamerazüge in einem Schritt mit tatsächlich freien Geräten an.
 */
class CameraSetupService
{
    /** Slot (Spalte in camera_configs) → Name im Validierungs-Array. */
    private const SLOTS = ['lens' => 'lens', 'tripod' => 'tripod', 'tripod_head' => 'tripod_head', 'large_lens_adapter' => 'large_lens_adapter'];

    public function __construct(
        private ItemAvailabilityService $availability,
        private CameraConfigService $configs,
        private SmartAssignmentService $smart,
        private SlackVorgangSync $slack,
    ) {}

    /**
     * Häufigster Gerätetyp je Slot in früheren Zügen mit dieser Kamera
     * (gleicher Gerätetyp, sonst dasselbe Gerät).
     *
     * @return array{types: array<string, ?int>, preferred: array<string, ?int>, basis: int}
     */
    public function learnedSetup(Item $camera, ?int $excludeProductionId = null): array
    {
        $sameType = $camera->geraetetyp_id
            ? Item::where('geraetetyp_id', $camera->geraetetyp_id)->pluck('id')->all()
            : [$camera->id];

        $history = CameraConfig::with(['lensItem', 'tripodItem', 'headItem', 'adapterItem'])
            ->whereIn('item_id', $sameType)
            ->when($excludeProductionId, fn ($q) => $q->where('production_id', '!=', $excludeProductionId))
            ->latest('id')
            ->limit(100)
            ->get();

        $relations = ['lens' => 'lensItem', 'tripod' => 'tripodItem', 'tripod_head' => 'headItem', 'large_lens_adapter' => 'adapterItem'];
        $types = [];
        $preferred = [];

        foreach ($relations as $slot => $relation) {
            $counts = [];
            $latestItem = [];
            foreach ($history as $config) {
                $component = $config->{$relation};
                if (! $component?->geraetetyp_id) {
                    continue;
                }
                $counts[$component->geraetetyp_id] = ($counts[$component->geraetetyp_id] ?? 0) + 1;
                $latestItem[$component->geraetetyp_id] ??= $component->id; // history ist neueste-zuerst
            }
            arsort($counts);
            $topType = array_key_first($counts);
            $types[$slot] = $topType;
            $preferred[$slot] = $topType ? $latestItem[$topType] : null;
        }

        return ['types' => $types, 'preferred' => $preferred, 'basis' => $history->count()];
    }

    /**
     * Konkretes Setup für die Kamera: je Slot ein freies Gerät des gelernten Typs.
     *
     * @param  int[]  $taken  bereits vergebene Geräte-IDs
     * @return array{items: array<string, ?Item>, basis: int}
     */
    public function suggestFor(Production $production, Item $camera, array $taken = [], $pool = null): array
    {
        $learned = $this->learnedSetup($camera, $production->id);
        $pool ??= $this->availability->assignableItemsFor($production, null, true)->where('is_available', true);
        $pool = $pool->filter(fn (Item $i) => ! in_array($i->id, $taken, true) && $i->id !== $camera->id);

        $items = [];
        foreach (self::SLOTS as $slot) {
            $typeId = $learned['types'][$slot];
            if (! $typeId) {
                $items[$slot] = null;

                continue;
            }
            $candidates = $pool->where('geraetetyp_id', $typeId);
            // Dasselbe Gerät wie zuletzt bevorzugen, sonst Eigentum vor Miete / niedrige Nummer.
            $items[$slot] = $candidates->firstWhere('id', $learned['preferred'][$slot]) ?? $this->smart->ranked($candidates)->first();
            if ($items[$slot]) {
                $taken[] = $items[$slot]->id;
                $pool = $pool->filter(fn (Item $i) => $i->id !== $items[$slot]->id);
            }
        }

        return ['items' => $items, 'basis' => $learned['basis']];
    }

    /**
     * Legt bis zu $anzahl Kamerazüge der Kameratypen $camType an.
     *
     * @return array{created: CameraConfig[], warnings: string[], basis: int}
     */
    public function createRigs(Production $production, Geraetetyp $camType, int $anzahl): array
    {
        $pool = $this->availability->assignableItemsFor($production, null, true)->where('is_available', true);
        $cameras = $this->smart->ranked($pool->where('geraetetyp_id', $camType->id))->take($anzahl);

        $next = $this->nextCamNumber($production);
        $taken = $cameras->pluck('id')->all();
        $created = [];
        $warnings = [];
        $basis = 0;

        foreach ($cameras as $camera) {
            $suggestion = $this->suggestFor($production, $camera, $taken, $pool);
            $basis = max($basis, $suggestion['basis']);

            $payload = ['camera' => $camera->id, 'cam_number' => 'Cam '.$next];
            foreach ($suggestion['items'] as $slot => $item) {
                $payload[$slot] = $item?->id;
                if ($item) {
                    $taken[] = $item->id;
                }
            }

            $result = $this->configs->create($production, $payload);
            if ($result['success']) {
                $created[] = $result['config'];
                $next++;
            } else {
                $warnings[] = "{$camera->bezeichnung} {$camera->nummer}: {$result['error']}";
            }
        }

        if (count($cameras) < $anzahl) {
            $warnings[] = ($anzahl - count($cameras)).' Kamera(s) nicht verfügbar.';
        }

        if ($created) {
            $this->slack->syncProduction($production);
        }

        return ['created' => $created, 'warnings' => $warnings, 'basis' => $basis];
    }

    public function nextCamNumber(Production $production): int
    {
        $max = $production->cameraConfigs()->pluck('cam_number')
            ->map(fn ($n) => preg_match('/(\d+)\s*$/', (string) $n, $m) ? (int) $m[1] : 0)
            ->max();

        return ($max ?? 0) + 1;
    }
}
