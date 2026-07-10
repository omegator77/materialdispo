<?php

namespace App\Services;

use App\Models\CameraConfig;
use App\Models\Item;
use App\Models\Mietvorgang;
use App\Models\Production;
use App\Models\Vermietvorgang;

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
        // 1. Mietfenster: mindestens einer der Mietvorgänge des Geräts muss
        //    den Produktionszeitraum vollständig abdecken. Bei mehreren
        //    Zuordnungen, von denen keine passt, ist "zu spät"/"zu früh" eine
        //    Annäherung — echte Mehrfachzuordnungen sind mit dieser Prüfung
        //    noch nicht ausführlich getestet (aktuell 0 Mietvorgänge im System).
        if ($item->suppliers_id) {
            $mietvorgaenge = $item->mietvorgaenge;

            if ($mietvorgaenge->isNotEmpty()) {
                // Wichtig: beide Seiten vor dem Vergleich auf reine 'Y-m-d'-Strings
                // normalisieren. $mv->rent_start ist ein Carbon-Objekt (cast 'date'),
                // $production->booking_start eine reine DB-Zeichenkette (Production
                // castet booking_start/booking_end nicht). Ein direkter Vergleich
                // Carbon <=> String vergleicht über __toString() lexikographisch
                // ("2026-07-20 00:00:00" vs. "2026-07-20") — bei exakt gleichem
                // Datum verliert der Carbon-Wert durch den Zeit-Suffix, obwohl die
                // Daten identisch sind. Nur an der exakten Grenze sichtbar, deshalb
                // lange unbemerkt.
                $covered = $mietvorgaenge->contains(
                    fn (Mietvorgang $mv) => $mv->rent_start->format('Y-m-d') <= $production->booking_start
                        && $mv->rent_end->format('Y-m-d') >= $production->booking_end
                );

                if (! $covered) {
                    $tooLate = $mietvorgaenge->contains(fn (Mietvorgang $mv) => $mv->rent_start->format('Y-m-d') > $production->booking_start);

                    return [
                        'available' => false,
                        'reason' => $tooLate ? 'Mietbeginn zu spät' : 'Mietende zu früh',
                    ];
                }
            }
        }

        // 2. Verleihfenster: Gerät ist während eines Verleihzeitraums (Vermietung an
        //    einen Kunden) für Produktionen nicht verfügbar, sofern sich der
        //    Zeitraum mit der Produktion überschneidet. Bewusst ohne
        //    isComplete()-Filter — ein abgeschlossener Vermietvorgang blockiert
        //    hier weiterhin bei Datumsüberlappung, exakt wie vor dem Umbau.
        $verleihConflict = $item->vermietvorgaenge()
            ->where('rent_start', '<=', $production->booking_end)
            ->where('rent_end', '>=', $production->booking_start)
            ->first();

        if ($verleihConflict) {
            return [
                'available' => false,
                'reason' => 'Vermietet an '.($verleihConflict->mieter->bezeichnung ?? 'Mieter gelöscht'),
            ];
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
     * Prüft ob ein Gerät einem Mietvorgang (Wareneingang) zugeordnet werden
     * darf: weder anderweitig geclaimt (siehe claimConflict()) noch in einer
     * überlappenden Produktion/Kamerakonfiguration verplant.
     * Gibt ['available' => bool, 'reason' => string|null] zurück.
     */
    public function checkForMiete(Item $item, Mietvorgang $mietvorgang): array
    {
        $periodStart = $mietvorgang->rent_start->format('Y-m-d');
        $periodEnd = $mietvorgang->rent_end->format('Y-m-d');

        $claim = $this->claimConflict($item, $periodStart, $periodEnd, excludeMietvorgangId: $mietvorgang->id);

        if ($claim) {
            return ['available' => false, 'reason' => $claim];
        }

        return $this->productionConflict($item, $periodStart, $periodEnd);
    }

    /**
     * Prüft ob ein Gerät einem Vermietvorgang (Warenausgang) zugeordnet
     * werden darf: weder anderweitig geclaimt (siehe claimConflict()) noch
     * in einer überlappenden Produktion/Kamerakonfiguration verplant.
     * Gibt ['available' => bool, 'reason' => string|null] zurück.
     */
    public function checkForVerleih(Item $item, Vermietvorgang $vermietvorgang): array
    {
        $periodStart = $vermietvorgang->rent_start->format('Y-m-d');
        $periodEnd = $vermietvorgang->rent_end->format('Y-m-d');

        $claim = $this->claimConflict($item, $periodStart, $periodEnd, excludeVermietvorgangId: $vermietvorgang->id);

        if ($claim) {
            return ['available' => false, 'reason' => $claim];
        }

        return $this->productionConflict($item, $periodStart, $periodEnd);
    }

    /**
     * Ein Gerät kann über item_mietvorgang/item_vermietvorgang gleichzeitig an
     * mehreren, zeitlich getrennten Vorgängen hängen — das ist gewünscht
     * (z. B. drei nicht überlappende Vermietungen desselben Geräts im Voraus).
     * Blockiert wird nur, wenn sich der angefragte Zeitraum tatsächlich mit
     * dem Zeitraum eines ANDEREN Vorgangs überschneidet UND dieser noch nicht
     * abgeschlossen ist (isComplete()) — ist der andere Vorgang bereits
     * abgeschlossen, gilt das Gerät als zurück/frei, unabhängig vom Datum.
     */
    private function claimConflict(Item $item, string $periodStart, string $periodEnd, ?int $excludeMietvorgangId = null, ?int $excludeVermietvorgangId = null): ?string
    {
        $conflictingMiete = $this->overlappingVorgaenge($item->mietvorgaenge(), $periodStart, $periodEnd, $excludeMietvorgangId, 'mietvorgaenge')
            ->first(fn (Mietvorgang $mv) => ! $mv->isComplete());

        if ($conflictingMiete) {
            return 'Bereits in überlappendem Mietvorgang: '.($conflictingMiete->bezeichnung ?? $conflictingMiete->supplier?->bezeichnung ?? 'unbekannt');
        }

        $conflictingVermiete = $this->overlappingVorgaenge($item->vermietvorgaenge(), $periodStart, $periodEnd, $excludeVermietvorgangId, 'vermietvorgaenge')
            ->first(fn (Vermietvorgang $vv) => ! $vv->isComplete());

        if ($conflictingVermiete) {
            return 'Bereits in überlappendem Vermietvorgang: '.($conflictingVermiete->bezeichnung ?? $conflictingVermiete->mieter?->bezeichnung ?? 'unbekannt');
        }

        return null;
    }

    /**
     * Alle Zuordnungen des Items über die angegebene Pivot-Relation, deren
     * VORGANG-eigener Zeitraum sich mit [$periodStart, $periodEnd]
     * überschneidet — optional eine ID ausgeschlossen (der aktuell geprüfte
     * Vorgang selbst darf sich nie mit sich selbst überschneiden). Reiner
     * Overlap-Scan ohne isComplete()-Filterung, die Aufrufer filtern bei
     * Bedarf selbst (claimConflict() blendet Abgeschlossene aus, check()
     * Fall 2 bewusst nicht).
     *
     * @param  \Illuminate\Database\Eloquent\Relations\BelongsToMany  $relation
     */
    private function overlappingVorgaenge($relation, string $periodStart, string $periodEnd, ?int $excludeId, string $relatedTable): \Illuminate\Support\Collection
    {
        return $relation
            ->when($excludeId, fn ($q) => $q->where("{$relatedTable}.id", '!=', $excludeId))
            ->where('rent_start', '<=', $periodEnd)
            ->where('rent_end', '>=', $periodStart)
            ->get();
    }

    /**
     * Prüft ob ein Gerät während eines beliebigen Zeitraums frei von
     * Produktions-/Kamerakonfigurations-Konflikten ist. Zeitraum-agnostischer
     * Baustein für checkForMiete()/checkForVerleih().
     * Gibt ['available' => bool, 'reason' => string|null] zurück.
     */
    private function productionConflict(Item $item, string $periodStart, string $periodEnd): array
    {
        $conflict = $item->productions()
            ->where('booking_start', '<=', $periodEnd)
            ->where('booking_end', '>=', $periodStart)
            ->first();

        if ($conflict) {
            return [
                'available' => false,
                'reason' => 'Gebucht in Produktion: '.$conflict->bezeichnung,
            ];
        }

        $configConflict = CameraConfig::query()
            ->whereHas('production', function ($q) use ($periodStart, $periodEnd) {
                $q->where('booking_start', '<=', $periodEnd)
                    ->where('booking_end', '>=', $periodStart);
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
            ->with(['mietvorgaenge', 'vermietvorgaenge'])
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
