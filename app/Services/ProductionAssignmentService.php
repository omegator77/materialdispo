<?php

namespace App\Services;

use App\Models\Item;
use App\Models\Production;
use Illuminate\Support\Facades\DB;

/**
 * Einziger Weg, Geräte einer Produktion zuzuordnen — von Picker, Massen-
 * aktion, Schnell-Hinzufügen und Auto-Zuordnung gemeinsam genutzt, damit
 * Verfügbarkeitsprüfung, Aktivitätslog und Slack-Sync überall identisch laufen.
 */
class ProductionAssignmentService
{
    public function __construct(
        private ItemAvailabilityService $availability,
        private SlackVorgangSync $slack,
    ) {}

    /**
     * @param  iterable<Item>  $items
     * @return array{added: Item[], skipped: array<int, array{item: Item, reason: string}>}
     */
    public function attachMany(Production $production, iterable $items, ?string $notes = null, bool $notifySlack = true): array
    {
        $added = [];
        $skipped = [];

        DB::transaction(function () use ($production, $items, $notes, &$added, &$skipped) {
            foreach ($items as $item) {
                if ($production->items()->where('items.id', $item->id)->exists()) {
                    $skipped[] = ['item' => $item, 'reason' => 'bereits enthalten'];

                    continue;
                }

                $check = $this->availability->check($item, $production);
                if (! $check['available']) {
                    $skipped[] = ['item' => $item, 'reason' => $check['reason']];

                    continue;
                }

                $production->items()->syncWithoutDetaching([$item->id => ['notes' => $notes]]);

                activity('item')
                    ->performedOn($item)
                    ->event('attached')
                    ->withProperties(['production' => $production->bezeichnung, 'production_id' => $production->id])
                    ->log("Gerät \"{$item->bezeichnung}\" zu Produktion \"{$production->bezeichnung}\" hinzugefügt");

                $added[] = $item;
            }
        });

        if ($notifySlack && $added) {
            $this->slack->syncProduction($production);
        }

        return ['added' => $added, 'skipped' => $skipped];
    }

    public function detach(Production $production, Item $item, bool $notifySlack = true): void
    {
        $production->items()->detach($item->id);

        activity('item')
            ->performedOn($item)
            ->event('detached')
            ->withProperties(['production' => $production->bezeichnung, 'production_id' => $production->id])
            ->log("Gerät \"{$item->bezeichnung}\" aus Produktion \"{$production->bezeichnung}\" entfernt");

        if ($notifySlack) {
            $this->slack->syncProduction($production);
        }
    }
}
