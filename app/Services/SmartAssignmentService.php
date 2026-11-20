<?php

namespace App\Services;

use App\Models\Geraetetyp;
use App\Models\Item;
use App\Models\Mietvorgang;
use App\Models\Production;
use App\Models\Unit;
use App\Models\Vermietvorgang;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Regelbasierte "Intelligenz" der Disposition: übersetzt einen Bedarf
 * ("3x LDX, 2 Stativ") in konkrete, tatsächlich freie Geräte, erkennt Lücken
 * zum VB-Protokoll, schlägt Ersatz bei Konflikten vor und leitet aus
 * früheren Produktionen ab, was meist zusammen gebucht wird.
 *
 * Auswahlregel pro Bedarf: Eigenmaterial vor Mietmaterial (keine unnötigen
 * Mietkosten/-fenster), dann niedrigste Gerätenummer — deterministisch.
 */
class SmartAssignmentService
{
    public function __construct(
        private ItemAvailabilityService $availability,
        private ProductionAssignmentService $assignment,
        private EquipmentMatcher $matcher,
    ) {}

    /**
     * Freitext → Bedarfszeilen mit aufgelöstem Gerätetyp/Gruppe.
     *
     * @return array<int, array{raw: string, query: string, anzahl: int, match: array}>
     */
    public function parseNeeds(string $text): array
    {
        $types = Geraetetyp::with('unit')->get();
        $units = Unit::all();

        return array_map(function (array $line) use ($types, $units) {
            $line['match'] = $this->matcher->match($line['query'], $types, $units);

            return $line;
        }, $this->matcher->parseLines($text));
    }

    /**
     * Plan (ohne Schreibzugriff): pro Bedarfszeile die gewählten Geräte,
     * die Fehlmenge, blockierte Kandidaten samt Grund und Ersatzkandidaten.
     *
     * @param  array<int, array{query?: string, anzahl: int, match: array}>  $needs
     * @return array{lines: array<int, array<string, mixed>>, items: Item[], missing: int}
     */
    public function plan(Production $production, array $needs): array
    {
        return $this->planFromPool($this->availability->assignableItemsFor($production, null, true), $needs);
    }

    /**
     * Wie plan(), aber für einen Miet-/Vermietvorgang: Kandidaten sind die
     * noch nicht zugeordneten Geräte (bei Miete nur die des Vermieters),
     * Verfügbarkeit nach den Regeln des jeweiligen Vorgangs.
     */
    public function planForVorgang(Mietvorgang|Vermietvorgang $vorgang, array $needs): array
    {
        $isMiete = $vorgang instanceof Mietvorgang;
        $pool = Item::query()
            ->whereDoesntHave($isMiete ? 'mietvorgaenge' : 'vermietvorgaenge', fn ($q) => $q->where($isMiete ? 'mietvorgaenge.id' : 'vermietvorgaenge.id', $vorgang->id))
            ->when($isMiete, fn ($q) => $q->where('suppliers_id', $vorgang->suppliers_id))
            ->get()
            ->map(function (Item $item) use ($vorgang, $isMiete) {
                $check = $isMiete ? $this->availability->checkForMiete($item, $vorgang) : $this->availability->checkForVerleih($item, $vorgang);
                $item->is_available = $check['available'];
                $item->availability_reason = $check['reason'];

                return $item;
            });

        return $this->planFromPool($pool, $needs);
    }

    /**
     * @param  array<int, array{query?: string, anzahl: int, match: array}>  $needs
     */
    public function planFromPool(Collection $pool, array $needs): array
    {
        $taken = [];
        $lines = [];
        $picked = [];
        $missing = 0;

        foreach ($needs as $need) {
            $match = $need['match'];
            $anzahl = max(1, (int) $need['anzahl']);

            if (! $match['kind']) {
                $lines[] = [
                    'label' => $need['query'] ?? '?', 'requested' => $anzahl, 'matched' => false,
                    'items' => [], 'missing' => $anzahl, 'blocked' => [], 'substitutes' => [], 'alternatives' => [],
                ];
                $missing += $anzahl;

                continue;
            }

            $candidates = $pool
                ->filter(fn (Item $i) => ! in_array($i->id, $taken, true))
                ->filter(fn (Item $i) => $match['kind'] === 'typ'
                    ? in_array($i->geraetetyp_id, $match['geraetetyp_ids'], true)
                    : $i->units_id === $match['unit_id']);

            $free = $this->ranked($candidates->where('is_available', true));
            $chosen = $free->take($anzahl)->values();
            foreach ($chosen as $item) {
                $taken[] = $item->id;
                $picked[] = $item;
            }

            $short = $anzahl - $chosen->count();
            $missing += $short;

            $blocked = [];
            $substitutes = [];
            if ($short > 0) {
                $blocked = $this->ranked($candidates->where('is_available', false))->take(3)
                    ->map(fn (Item $i) => ['item' => $i, 'reason' => $i->availability_reason])->values()->all();

                // Ersatz: freie Geräte derselben Gruppe, aber anderer Typ.
                $unitIds = $match['unit_ids'];
                $substitutes = $this->ranked(
                    $pool->where('is_available', true)
                        ->filter(fn (Item $i) => ! in_array($i->id, $taken, true) && in_array($i->units_id, $unitIds, true))
                        ->filter(fn (Item $i) => $match['kind'] === 'gruppe' || ! in_array($i->geraetetyp_id, $match['geraetetyp_ids'], true))
                )->take(3)->values()->all();
            }

            $lines[] = [
                'label' => $match['label'], 'requested' => $anzahl, 'matched' => true, 'kind' => $match['kind'],
                'items' => $chosen->all(), 'missing' => $short, 'blocked' => $blocked, 'substitutes' => $substitutes,
                'alternatives' => $match['alternatives'],
            ];
        }

        return ['lines' => $lines, 'items' => $picked, 'missing' => $missing];
    }

    /**
     * @param  array{items: Item[]}  $plan
     * @return array{added: Item[], skipped: array}
     */
    public function apply(Production $production, array $plan): array
    {
        return $this->assignment->attachMany($production, $plan['items']);
    }

    /**
     * Offene Bedarfe aus den (Nicht-Kamera-)Anforderungen des VB-Protokolls:
     * Soll je Gerätetyp bzw. Gruppe (aufsummiert) minus bereits zugeordnete Geräte.
     * Kameraanforderungen (Kamerazüge) bleiben manuell, da sie eine Konfiguration brauchen.
     *
     * @return array<int, array{query: string, anzahl: int, match: array}>
     */
    public function openVbNeeds(Production $production): array
    {
        $production->loadMissing(['vbProtokoll.anforderungen.geraetetyp', 'vbProtokoll.anforderungen.unit', 'items', 'cameraConfigs.item', 'cameraConfigs.lensItem', 'cameraConfigs.tripodItem', 'cameraConfigs.headItem', 'cameraConfigs.adapterItem']);

        if (! $production->vbProtokoll) {
            return [];
        }

        $sollTyp = [];
        $sollGruppe = [];
        foreach ($production->vbProtokoll->anforderungen as $a) {
            if ($a->cam_number !== null || ! $a->anzahl) {
                continue;
            }
            if ($a->geraetetyp_id) {
                $sollTyp[$a->geraetetyp_id] = ($sollTyp[$a->geraetetyp_id] ?? 0) + $a->anzahl;
            } elseif ($a->unit_id) {
                $sollGruppe[$a->unit_id] = ($sollGruppe[$a->unit_id] ?? 0) + $a->anzahl;
            }
        }

        $entries = $production->packlistEntries();
        $needs = [];

        foreach ($sollTyp as $typId => $soll) {
            $fehl = $soll - $entries->filter(fn ($e) => $e['item']->geraetetyp_id === $typId)->count();
            if ($fehl > 0) {
                $typ = Geraetetyp::find($typId);
                $needs[] = ['query' => $typ->bezeichnung, 'anzahl' => $fehl, 'match' => [
                    'kind' => 'typ', 'score' => 1.0, 'label' => $typ->bezeichnung, 'geraetetyp_ids' => [$typId],
                    'unit_id' => $typ->units_id, 'unit_ids' => [$typ->units_id], 'alternatives' => [],
                ]];
            }
        }
        foreach ($sollGruppe as $unitId => $soll) {
            $fehl = $soll - $entries->filter(fn ($e) => $e['item']->units_id === $unitId)->count();
            if ($fehl > 0) {
                $unit = Unit::find($unitId);
                $needs[] = ['query' => $unit->bezeichnung, 'anzahl' => $fehl, 'match' => [
                    'kind' => 'gruppe', 'score' => 1.0, 'label' => $unit->bezeichnung, 'geraetetyp_ids' => [],
                    'unit_id' => $unitId, 'unit_ids' => [$unitId], 'alternatives' => [],
                ]];
            }
        }

        return $needs;
    }

    /**
     * Ein freies Ersatzgerät für $item in $production: bevorzugt derselbe
     * Gerätetyp, sonst dieselbe Gruppe.
     */
    public function replacementFor(Production $production, Item $item): ?Item
    {
        $pool = $this->availability->assignableItemsFor($production, null, true)
            ->where('is_available', true)
            ->where('id', '!=', $item->id);

        $sameType = $item->geraetetyp_id
            ? $pool->where('geraetetyp_id', $item->geraetetyp_id)
            : collect();
        $candidates = $sameType->isNotEmpty() ? $sameType : $pool->where('units_id', $item->units_id);

        return $this->ranked($candidates)->first();
    }

    /**
     * Ersetzt $old durch ein freies Ersatzgerät. Rückgabe: das neue Gerät
     * oder null (dann bleibt alles unverändert).
     */
    public function replaceItem(Production $production, Item $old): ?Item
    {
        $new = $this->replacementFor($production, $old);
        if (! $new) {
            return null;
        }

        $notes = $production->items()->where('items.id', $old->id)->first()?->pivot?->notes;

        $result = DB::transaction(function () use ($production, $old, $new, $notes) {
            $this->assignment->detach($production, $old, notifySlack: false);

            return $this->assignment->attachMany($production, [$new], $notes, notifySlack: false);
        });

        if (! $result['added']) {
            // Ersatz wurde doch abgelehnt: alten Zustand wiederherstellen.
            $production->items()->syncWithoutDetaching([$old->id => ['notes' => $notes]]);

            return null;
        }

        app(SlackVorgangSync::class)->syncProduction($production);

        return $new;
    }

    /**
     * "Wird oft zusammen gebucht": Geräte, die in früheren Produktionen mit
     * den bereits zugeordneten Geräten gemeinsam auftauchten, aber hier noch
     * fehlen — nur verfügbare, nach Häufigkeit sortiert.
     *
     * @return array<int, array{item: Item, together: int, of: int}>
     */
    public function suggestionsFor(Production $production, int $limit = 6): array
    {
        $current = $production->items()->pluck('items.id')->all();
        if (! $current) {
            return [];
        }

        $rows = DB::table('item_production as a')
            ->join('item_production as b', fn ($j) => $j->on('a.production_id', '=', 'b.production_id')->on('a.item_id', '!=', 'b.item_id'))
            ->whereIn('a.item_id', $current)
            ->whereNotIn('b.item_id', $current)
            ->where('a.production_id', '!=', $production->id)
            ->groupBy('b.item_id')
            ->select('b.item_id', DB::raw('count(distinct a.production_id) as together'))
            ->orderByDesc('together')
            ->limit($limit * 3)
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $items = Item::whereIn('id', $rows->pluck('item_id'))->get()->keyBy('id');
        $of = DB::table('item_production')->whereIn('item_id', $current)->where('production_id', '!=', $production->id)->distinct()->count('production_id');
        $assignable = $this->availability->assignableItemsFor($production, null, false)->keyBy('id');

        $out = [];
        foreach ($rows as $row) {
            if (isset($assignable[$row->item_id]) && isset($items[$row->item_id])) {
                $out[] = ['item' => $items[$row->item_id], 'together' => (int) $row->together, 'of' => $of];
            }
            if (count($out) >= $limit) {
                break;
            }
        }

        return $out;
    }

    /**
     * Frühere Produktion mit ähnlichem Namen (und Material) als Vorlage.
     */
    public function similarProduction(Production $production): ?Production
    {
        $tokens = array_values(array_filter(explode(' ', $this->matcher->normalize($production->bezeichnung)), fn ($t) => strlen($t) >= 3 && ! ctype_digit($t)));
        if (! $tokens) {
            return null;
        }

        $best = null;
        $bestScore = 0.0;

        Production::where('id', '!=', $production->id)
            ->whereHas('items')
            ->withCount('items')
            ->orderByDesc('booking_start')
            ->limit(200)
            ->get()
            ->each(function (Production $other) use ($tokens, &$best, &$bestScore) {
                $otherTokens = array_filter(explode(' ', $this->matcher->normalize($other->bezeichnung)), fn ($t) => strlen($t) >= 3 && ! ctype_digit($t));
                $shared = count(array_intersect($tokens, $otherTokens));
                $union = count(array_unique(array_merge($tokens, $otherTokens)));
                $score = $union ? $shared / $union : 0;
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $best = $other;
                }
            });

        return $bestScore >= 0.4 ? $best : null;
    }

    /**
     * Plan als JSON-taugliches Array (Live-Vorschau im Browser).
     */
    public function serializePlan(array $plan): array
    {
        $fmt = fn (Item $i) => ['id' => $i->id, 'label' => trim($i->bezeichnung.' '.($i->nummer ? "({$i->nummer})" : '')), 'rented' => (bool) $i->suppliers_id];

        return [
            'missing' => $plan['missing'],
            'total' => count($plan['items']),
            'lines' => array_map(fn ($l) => [
                'label' => $l['label'],
                'requested' => $l['requested'],
                'matched' => $l['matched'],
                'missing' => $l['missing'],
                'items' => array_map($fmt, $l['items']),
                'blocked' => array_map(fn ($b) => $fmt($b['item']) + ['reason' => $b['reason']], $l['blocked']),
                'substitutes' => array_map($fmt, $l['substitutes']),
            ], $plan['lines']),
        ];
    }

    public function ranked(Collection $items): Collection
    {
        return $items->sortBy([
            fn ($a, $b) => (int) (bool) $a->suppliers_id <=> (int) (bool) $b->suppliers_id, // Eigentum zuerst
            fn ($a, $b) => strnatcasecmp((string) $a->nummer, (string) $b->nummer),
            fn ($a, $b) => $a->id <=> $b->id,
        ])->values();
    }
}
