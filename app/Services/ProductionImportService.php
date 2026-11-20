<?php

namespace App\Services;

use App\Models\CameraConfig;
use App\Models\Item;
use App\Models\Production;
use Illuminate\Support\Facades\DB;

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
     * Vorlagen-Produktion in die Zielproduktion. Nur Geräte/Kamerazüge der
     * Vorlage werden akzeptiert, jedes Gerät wird erneut auf Verfügbarkeit
     * geprüft (die Vorschau kann veraltet sein), alles läuft in einer
     * Transaktion unter Geräte-Zeilensperre.
     *
     * @return array{imported: int, skipped: list<string>}
     */
    public function applyImport(Production $target, Production $source, array $itemsInput, array $configsInput): array
    {
        $source->loadMissing('items', 'cameraConfigs');

        return DB::transaction(function () use ($target, $source, $itemsInput, $configsInput) {
            $imported = 0;
            $skipped = [];

            $this->availability->lockItems($this->involvedItemIds($source, $itemsInput, $configsInput));

            foreach ($itemsInput as $itemId => $data) {
                $action = $data['action'] ?? 'skip';

                if ($action === 'keep') {
                    $item = $source->items->firstWhere('id', (int) $itemId);

                    if (! $item) {
                        continue;
                    }

                    if ($this->attach($target, $item, $data['notes'] ?? null, $skipped)) {
                        $imported++;
                    }
                } elseif ($action === 'replace' && ! empty($data['replacement_id'])) {
                    if (! $source->items->contains('id', (int) $itemId)) {
                        continue;
                    }

                    $replacement = Item::find($data['replacement_id']);

                    if ($replacement && $this->attach($target, $replacement, $data['notes'] ?? null, $skipped)) {
                        $imported++;
                    }
                }
            }

            foreach ($configsInput as $configId => $action) {
                if ($action !== 'import') {
                    continue;
                }

                $config = $source->cameraConfigs->firstWhere('id', (int) $configId);
                if (! $config) {
                    continue;
                }

                if ($conflict = $this->configConflict($config, $target)) {
                    $skipped[] = "Kamerazug {$config->cam_number} ({$conflict})";

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
                $imported++;
            }

            return ['imported' => $imported, 'skipped' => $skipped];
        });
    }

    /**
     * Hängt ein Gerät an die Zielproduktion, sofern es verfügbar ist.
     * Nicht verfügbare Geräte landen mit Grund in $skipped.
     */
    private function attach(Production $target, Item $item, ?string $notes, array &$skipped): bool
    {
        if ($overlap = $this->availability->productionOverlap($item, $target)) {
            $skipped[] = "{$item->bezeichnung} ({$overlap})";

            return false;
        }

        $check = $this->availability->check($item, $target);

        if (! $check['available']) {
            $skipped[] = "{$item->bezeichnung} ({$check['reason']})";

            return false;
        }

        $target->items()->syncWithoutDetaching([$item->id => ['notes' => $notes]]);

        return true;
    }

    /** Erste Konfliktmeldung eines Kamerazugs (Kamera + Zubehör) oder null. */
    private function configConflict(CameraConfig $config, Production $target): ?string
    {
        foreach ([$config->item_id, $config->lens, $config->tripod, $config->tripod_head, $config->large_lens_adapter] as $id) {
            if (! $id || ! ($item = Item::find($id))) {
                continue;
            }

            if ($overlap = $this->availability->productionOverlap($item, $target)) {
                return "{$item->bezeichnung}: {$overlap}";
            }

            $check = $this->availability->check($item, $target);

            if (! $check['available']) {
                return "{$item->bezeichnung}: {$check['reason']}";
            }
        }

        return null;
    }

    /** @return list<int|string> */
    private function involvedItemIds(Production $source, array $itemsInput, array $configsInput): array
    {
        $ids = collect($itemsInput)->keys()
            ->merge(collect($itemsInput)->pluck('replacement_id'));

        foreach ($configsInput as $configId => $action) {
            if ($action === 'import' && ($config = $source->cameraConfigs->firstWhere('id', (int) $configId))) {
                $ids->push($config->item_id, $config->lens, $config->tripod, $config->tripod_head, $config->large_lens_adapter);
            }
        }

        return $ids->filter()->all();
    }
}
