<?php

namespace App\Services;

use App\Models\CameraConfig;
use App\Models\Item;
use App\Models\Production;

class ProductionImportService
{
    public function __construct(private ItemAvailabilityService $availability) {}

    /**
     * Baut die Vorschau für die Übernahme von Geräten und Kamerazügen aus
     * einer Vorlagen-Produktion in die Zielproduktion, inkl. Verfügbarkeits-
     * prüfung und Alternativen-Vorschlägen für nicht verfügbare Einzelgeräte.
     */
    public function buildImportPreview(Production $target, Production $source): array
    {
        $source->load([
            'items.unit',
            'cameraConfigs.item.unit',
            'cameraConfigs.lensItem',
            'cameraConfigs.tripodItem',
            'cameraConfigs.headItem',
            'cameraConfigs.adapterItem',
        ]);

        $itemResults = $source->items->map(function ($item) use ($target) {
            $check = $this->availability->check($item, $target);
            $alternatives = collect();
            if (! $check['available']) {
                $alternatives = Item::where('units_id', $item->units_id)
                    ->where('id', '!=', $item->id)
                    ->orderBy('bezeichnung')
                    ->get()
                    ->filter(fn ($alt) => $this->availability->check($alt, $target)['available'])
                    ->values();
            }

            return [
                'item' => $item,
                'available' => $check['available'],
                'reason' => $check['reason'],
                'notes' => $item->pivot->notes ?? null,
                'alternatives' => $alternatives,
            ];
        });

        $configResults = $source->cameraConfigs->map(function ($config) use ($target) {
            $slots = [
                'Kamera' => $config->item,
                'Objektiv' => $config->lensItem,
                'Stativ' => $config->tripodItem,
                'Stativkopf' => $config->headItem,
                'Adapter' => $config->adapterItem,
            ];

            $conflicts = [];
            foreach ($slots as $label => $item) {
                if (! $item) {
                    continue;
                }
                $check = $this->availability->check($item, $target);
                if (! $check['available']) {
                    $conflicts[$label] = ['item' => $item, 'reason' => $check['reason']];
                }
            }

            return [
                'config' => $config,
                'slots' => array_filter($slots),
                'available' => empty($conflicts),
                'conflicts' => $conflicts,
            ];
        });

        return [
            'itemResults' => $itemResults,
            'configResults' => $configResults,
        ];
    }

    /**
     * Übernimmt die vom User ausgewählten Geräte und Kamerazüge aus der
     * Vorlagen-Produktion in die Zielproduktion.
     */
    public function applyImport(Production $target, Production $source, array $itemsInput, array $configsInput): void
    {
        foreach ($itemsInput as $itemId => $data) {
            $action = $data['action'] ?? 'skip';

            if ($action === 'keep') {
                $target->items()->syncWithoutDetaching([
                    $itemId => ['notes' => $data['notes'] ?? null],
                ]);
            } elseif ($action === 'replace' && ! empty($data['replacement_id'])) {
                $replacement = Item::find($data['replacement_id']);
                if ($replacement && $this->availability->check($replacement, $target)['available']) {
                    $target->items()->syncWithoutDetaching([
                        $data['replacement_id'] => ['notes' => $data['notes'] ?? null],
                    ]);
                }
            }
        }

        foreach ($configsInput as $configId => $action) {
            if ($action !== 'import') {
                continue;
            }

            $config = $source->cameraConfigs->firstWhere('id', $configId);
            if (! $config) {
                continue;
            }

            $newConfig = new CameraConfig;
            $newConfig->production_id = $target->id;
            $newConfig->item_id = $config->item_id;
            $newConfig->lens = $config->lens;
            $newConfig->tripod = $config->tripod;
            $newConfig->tripod_head = $config->tripod_head;
            $newConfig->large_lens_adapter = $config->large_lens_adapter;
            $newConfig->cam_number = $config->cam_number;
            $newConfig->notes = $config->notes;
            $newConfig->save();
        }
    }
}
