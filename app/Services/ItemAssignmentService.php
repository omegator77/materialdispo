<?php

namespace App\Services;

use App\Models\Item;
use App\Models\Mietvorgang;
use App\Models\Vermietvorgang;

/**
 * Einziger Weg, ein Gerät einem Miet-/Vermietvorgang zuzuordnen oder daraus
 * zu entfernen — genutzt sowohl vom Picker auf der Mietvorgang-/
 * Vermietvorgang-Seite (manual: true) als auch von der automatischen
 * Schnellzuordnung über das Geräte-Formular (manual: false, siehe
 * Item::syncMietvorgang()/syncVermietvorgang()). Vorher liefen beide Wege
 * unabhängig voneinander; das führte dazu, dass Konfliktprüfungen an einer
 * Stelle nachgezogen wurden und an der anderen nicht.
 */
class ItemAssignmentService
{
    public function __construct(
        private ItemAvailabilityService $availability,
        private SlackVorgangSync $slack,
    ) {}

    /**
     * @return array{added: bool, alreadyAttached: bool, reason: ?string}
     */
    public function attachToMietvorgang(Item $item, Mietvorgang $mietvorgang, bool $manual, bool $notifySlack = true): array
    {
        if ($item->mietvorgaenge()->where('mietvorgaenge.id', $mietvorgang->id)->exists()) {
            return ['added' => false, 'alreadyAttached' => true, 'reason' => null];
        }

        $check = $this->availability->checkForMiete($item, $mietvorgang);

        if (! $check['available']) {
            return ['added' => false, 'alreadyAttached' => false, 'reason' => $check['reason']];
        }

        $item->mietvorgaenge()->syncWithoutDetaching([$mietvorgang->id => ['manual' => $manual]]);

        activity('item')
            ->performedOn($item)
            ->event('attached')
            ->withProperties(['mietvorgang_id' => $mietvorgang->id])
            ->log("Gerät \"{$item->bezeichnung}\" dem Mietvorgang ({$mietvorgang->supplier->bezeichnung}) zugeordnet");

        if ($notifySlack) {
            $this->slack->syncMietvorgang($mietvorgang);
        }

        return ['added' => true, 'alreadyAttached' => false, 'reason' => null];
    }

    public function detachFromMietvorgang(Item $item, Mietvorgang $mietvorgang, bool $notifySlack = true): void
    {
        $item->mietvorgaenge()->detach($mietvorgang->id);

        if ($notifySlack) {
            $this->slack->syncMietvorgang($mietvorgang);
        }
    }

    /**
     * @return array{added: bool, alreadyAttached: bool, reason: ?string}
     */
    public function attachToVermietvorgang(Item $item, Vermietvorgang $vermietvorgang, bool $manual, bool $notifySlack = true): array
    {
        if ($item->vermietvorgaenge()->where('vermietvorgaenge.id', $vermietvorgang->id)->exists()) {
            return ['added' => false, 'alreadyAttached' => true, 'reason' => null];
        }

        $check = $this->availability->checkForVerleih($item, $vermietvorgang);

        if (! $check['available']) {
            return ['added' => false, 'alreadyAttached' => false, 'reason' => $check['reason']];
        }

        $item->vermietvorgaenge()->syncWithoutDetaching([$vermietvorgang->id => ['manual' => $manual]]);

        activity('item')
            ->performedOn($item)
            ->event('attached')
            ->withProperties(['vermietvorgang_id' => $vermietvorgang->id])
            ->log("Gerät \"{$item->bezeichnung}\" dem Vermietvorgang ({$vermietvorgang->mieter->bezeichnung}) zugeordnet");

        if ($notifySlack) {
            $this->slack->syncVermietvorgang($vermietvorgang);
        }

        return ['added' => true, 'alreadyAttached' => false, 'reason' => null];
    }

    public function detachFromVermietvorgang(Item $item, Vermietvorgang $vermietvorgang, bool $notifySlack = true): void
    {
        $item->vermietvorgaenge()->detach($vermietvorgang->id);

        if ($notifySlack) {
            $this->slack->syncVermietvorgang($vermietvorgang);
        }
    }
}
