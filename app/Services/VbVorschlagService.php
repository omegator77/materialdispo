<?php

namespace App\Services;

use App\Models\CameraConfig;
use App\Models\Item;
use App\Models\Production;
use App\Models\VbProtokollAnforderung;
use Illuminate\Support\Collection;

/**
 * Leitet aus den strukturierten Anforderungen des VB-Protokolls einen Vorschlag
 * ab: Kamerazüge und übriges Material, je mit freien konkreten Geräten. Reine
 * Berechnung ohne Schreibzugriff.
 */
class VbVorschlagService
{
    /** Slot => [Spalte der Anforderung mit dem verlangten Typ, Bezeichnung]. */
    private const SLOTS = [
        'camera' => ['geraetetyp_id', 'Kamera'],
        'lens' => ['lens_geraetetyp_id', 'Objektiv'],
        'tripod' => ['tripod_geraetetyp_id', 'Stativ'],
        'tripod_head' => ['tripod_head_geraetetyp_id', 'Stativkopf'],
        'large_lens_adapter' => ['adapter_geraetetyp_id', 'Adapter'],
    ];

    public function __construct(
        private ItemAvailabilityService $availability,
        private SmartAssignmentService $smart,
        private CameraSetupService $cameraSetup,
        private CameraConfigService $cameraConfigs,
        private ProductionAssignmentService $assignments,
        private SlackVorgangSync $slack,
    ) {}

    /**
     * @return array{rigs: list<array<string, mixed>>, needs: list<array<string, mixed>>, empty: bool}
     */
    public function propose(Production $production): array
    {
        $production->loadMissing([
            'vbProtokoll.anforderungen.geraetetyp', 'vbProtokoll.anforderungen.lensGeraetetyp',
            'vbProtokoll.anforderungen.tripodGeraetetyp', 'vbProtokoll.anforderungen.tripodHeadGeraetetyp',
            'vbProtokoll.anforderungen.adapterGeraetetyp', 'cameraConfigs.item',
        ]);

        if (! $production->vbProtokoll) {
            return ['rigs' => [], 'needs' => [], 'empty' => true];
        }

        // Einmal laden: freie Geräte; vergebene IDs wandern in $taken (über Züge und Material).
        $pool = $this->availability->assignableItemsFor($production, null, true)->where('is_available', true)->values();
        $taken = [];

        $rigs = $this->rigs($production, $pool, $taken);
        $needs = $this->needs($production, $pool, $taken);

        return ['rigs' => $rigs, 'needs' => $needs, 'empty' => ! $rigs && ! $needs];
    }

    /**
     * Übernimmt die gewählten Züge und das Material. Jeder Zug ganz oder gar nicht;
     * Fehlschläge landen in 'skipped', der Rest läuft weiter. Die Geräte werden von den
     * Services erneut geprüft, die Struktur nicht aus dem VB-Protokoll neu abgeleitet.
     *
     * @param  array{rigs?: list<array<string, mixed>>, items?: list<int>}  $auswahl
     * @return array{created: list<CameraConfig>, attached: list<Item>, skipped: list<string>, warnings: list<string>}
     */
    public function apply(Production $production, array $auswahl): array
    {
        $created = [];
        $skipped = [];
        $warnings = [];
        $next = $this->cameraSetup->nextCamNumber($production);

        foreach ($auswahl['rigs'] ?? [] as $rig) {
            if (empty($rig['take']) || empty($rig['camera'])) {
                continue;
            }

            $result = $this->cameraConfigs->create($production, [
                'camera' => (int) $rig['camera'],
                'lens' => $rig['lens'] ?? null,
                'tripod' => $rig['tripod'] ?? null,
                'tripod_head' => $rig['tripod_head'] ?? null,
                'large_lens_adapter' => $rig['large_lens_adapter'] ?? null,
                'cam_number' => 'Cam '.$next,
            ]);

            if ($result['success']) {
                $created[] = $result['config'];
                $next++;
            } else {
                $skipped[] = 'Kamerazug: '.$result['error'];
            }
        }

        $attached = [];
        $ids = collect($auswahl['items'] ?? [])->filter()->unique()->values();
        if ($ids->isNotEmpty()) {
            $result = $this->assignments->attachMany($production, Item::whereIn('id', $ids)->get(), notifySlack: false);
            $attached = $result['added'];
            foreach ($result['skipped'] as $row) {
                $skipped[] = $row['item']->bezeichnung.': '.$row['reason'];
            }
            $warnings = $result['warnings'];
        }

        if ($created || $attached) {
            $this->slack->syncProduction($production);
        }

        return ['created' => $created, 'attached' => $attached, 'skipped' => $skipped, 'warnings' => $warnings];
    }

    /**
     * @param  list<int>  $taken
     * @return list<array<string, mixed>>
     */
    private function rigs(Production $production, Collection $pool, array &$taken): array
    {
        // Je Kamera-Typ decken vorhandene Züge die ersten VB-Zeilen dieses Typs.
        $skip = $production->cameraConfigs->pluck('item.geraetetyp_id')->filter()->countBy()->all();
        $next = $this->cameraSetup->nextCamNumber($production);
        $rigs = [];

        $lines = $production->vbProtokoll->anforderungen->filter(fn (VbProtokollAnforderung $a) => $a->cam_number !== null);

        foreach ($lines as $line) {
            $typeId = $line->geraetetyp_id;
            if ($typeId && ($skip[$typeId] ?? 0) > 0) {
                $skip[$typeId]--;

                continue;
            }

            $noType = ! $typeId;
            $slots = [];
            $noCamera = false;

            foreach (self::SLOTS as $slot => [$column, $label]) {
                $slotTypeId = $line->{$column};
                $typ = $slotTypeId ? $this->typeOf($line, $slot) : null;
                $candidates = $slotTypeId
                    ? $this->smart->ranked($pool->filter(fn (Item $i) => $i->geraetetyp_id === $slotTypeId && ! in_array($i->id, $taken, true)))
                    : collect();

                // Ohne Kamera (kein Typ oder keine freie) gibt es keinen Zug: Zubehör wird nicht reserviert.
                $item = ($slot === 'camera' || ! $noCamera) ? $candidates->first() : null;
                if ($item) {
                    $taken[] = $item->id;
                }
                if ($slot === 'camera') {
                    $noCamera = $noType || ! $item;
                }

                $slots[$slot] = [
                    'label' => $label,
                    'typ' => $typ,
                    'item' => $item,
                    'alternatives' => $candidates->reject(fn (Item $i) => $i->id === $item?->id)->values(),
                    'gap' => ! $noType && $slotTypeId && ! $item,
                    'warning' => $item?->suppliers_id ? $this->availability->check($item, $production)['warning'] : null,
                ];
            }

            $rigs[] = [
                'line_id' => $line->id,
                'cam_label' => 'Cam '.$next++,
                'camera_missing' => ! $noType && $noCamera,
                'no_type' => $noType,
                'takeable' => $slots['camera']['item'] !== null,
                'slots' => $slots,
            ];
        }

        return $rigs;
    }

    /**
     * @param  list<int>  $taken
     * @return list<array<string, mixed>>
     */
    private function needs(Production $production, Collection $pool, array $taken): array
    {
        $needs = $this->smart->openVbNeeds($production);
        if (! $needs) {
            return [];
        }

        $free = $pool->reject(fn (Item $i) => in_array($i->id, $taken, true))->values();
        $plan = $this->smart->planFromPool($free, $needs);

        return collect($plan['lines'])->map(function (array $line, int $i) use ($needs, $free) {
            $match = $needs[$i]['match'];
            $chosen = collect($line['items'])->pluck('id');
            $alternatives = $this->smart->ranked($free->filter(fn (Item $item) => ! $chosen->contains($item->id)
                && ($match['kind'] === 'typ'
                    ? in_array($item->geraetetyp_id, $match['geraetetyp_ids'], true)
                    : $item->units_id === $match['unit_id'])));

            return [
                'label' => $line['label'],
                'requested' => $line['requested'],
                'missing' => $line['missing'],
                'items' => $line['items'],
                'alternatives' => $alternatives,
            ];
        })->all();
    }

    private function typeOf(VbProtokollAnforderung $line, string $slot): mixed
    {
        return match ($slot) {
            'camera' => $line->geraetetyp,
            'lens' => $line->lensGeraetetyp,
            'tripod' => $line->tripodGeraetetyp,
            'tripod_head' => $line->tripodHeadGeraetetyp,
            'large_lens_adapter' => $line->adapterGeraetetyp,
        };
    }
}
