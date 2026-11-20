<?php

namespace App\Services;

use App\Models\Production;
use Carbon\Carbon;

/**
 * "Handlungsbedarf" fürs Dashboard: erkennt, was jetzt Aufmerksamkeit
 * braucht, und liefert pro Befund gleich die Ein-Klick-Aktion mit.
 */
class AttentionService
{
    public function __construct(
        private ItemAvailabilityService $availability,
        private SmartAssignmentService $smart,
    ) {}

    /**
     * @return array<int, array{severity: string, kind: string, title: string, detail: string, url: string, action: ?array{label: string, url: string, confirm: ?string}}>
     */
    public function findings(int $horizonDays = 45): array
    {
        $today = Carbon::today();
        $productions = Production::with([
            'items.mietvorgaenge', 'items.vermietvorgaenge', 'cameraConfigs.item', 'cameraConfigs.lensItem',
            'cameraConfigs.tripodItem', 'cameraConfigs.headItem', 'cameraConfigs.adapterItem', 'itemPacks',
            'vbProtokoll.anforderungen',
        ])
            ->where('booking_end', '>=', $today->toDateString())
            ->where('booking_start', '<=', $today->copy()->addDays($horizonDays)->toDateString())
            ->orderBy('booking_start')
            ->get();

        $findings = [];
        $seenConflicts = [];

        foreach ($productions as $production) {
            $url = route('productions.show', $production);
            $start = Carbon::parse($production->booking_start);
            $daysUntil = (int) $today->diffInDays($start, false);
            $when = $daysUntil > 0 ? "in {$daysUntil} Tagen" : ($daysUntil === 0 ? 'heute' : 'läuft bereits');

            // 1. Konflikte: Gerät nicht (mehr) verfügbar.
            foreach ($production->items as $item) {
                $check = $this->availability->check($item, $production);
                if ($check['available']) {
                    continue;
                }

                // Doppelbuchung zweier Produktionen nur einmal melden (nicht je Seite).
                $other = str_starts_with($check['reason'], 'Gebucht in Produktion: ')
                    ? substr($check['reason'], strlen('Gebucht in Produktion: '))
                    : $check['reason'];
                $pair = [$production->bezeichnung, $other];
                sort($pair);
                $key = $item->id.'|'.implode('|', $pair);
                if (isset($seenConflicts[$key])) {
                    continue;
                }
                $seenConflicts[$key] = true;

                $replacement = $this->smart->replacementFor($production, $item);
                $findings[] = [
                    'severity' => 'rot',
                    'kind' => 'konflikt',
                    'title' => "{$item->bezeichnung}".($item->nummer ? " ({$item->nummer})" : '').' — Konflikt',
                    'detail' => "{$production->bezeichnung} ({$when}): {$check['reason']}",
                    'url' => $url,
                    'action' => $replacement ? [
                        'label' => 'Tauschen gegen '.$replacement->bezeichnung.($replacement->nummer ? " ({$replacement->nummer})" : ''),
                        'url' => route('productions.replaceItem', [$production, $item]),
                        'confirm' => null,
                    ] : null,
                ];
            }

            // 2. Leere Produktion kurz vor Start.
            if ($production->packlistEntries()->isEmpty() && $daysUntil <= 14) {
                $similar = $this->smart->similarProduction($production);
                $findings[] = [
                    'severity' => 'gelb',
                    'kind' => 'leer',
                    'title' => "{$production->bezeichnung}: noch kein Material",
                    'detail' => "Start {$when}".($similar ? " · ähnlich zu „{$similar->bezeichnung}“" : ''),
                    'url' => $url,
                    'action' => $similar ? [
                        'label' => 'Material von „'.$similar->bezeichnung.'“ übernehmen',
                        'url' => route('productions.importFrom', [$production, $similar]),
                        'method' => 'GET',
                        'confirm' => null,
                    ] : null,
                ];

                continue;
            }

            // 3. VB-Protokoll nicht gedeckt → Ein-Klick-Auto-Zuordnung.
            $needs = $this->smart->openVbNeeds($production);
            if ($needs) {
                $plan = $this->smart->plan($production, $needs);
                $offen = array_sum(array_map(fn ($n) => $n['anzahl'], $needs));
                $findings[] = [
                    'severity' => $daysUntil <= 7 ? 'rot' : 'gelb',
                    'kind' => 'vb_offen',
                    'title' => "{$production->bezeichnung}: {$offen} Position(en) aus dem VB-Protokoll offen",
                    'detail' => count($plan['items']).' davon sofort zuordenbar'.($plan['missing'] ? ", {$plan['missing']} nicht verfügbar" : '')." · Start {$when}",
                    'url' => $url,
                    'action' => $plan['items'] ? [
                        'label' => 'Packliste vorschlagen',
                        'url' => route('productions.vbVorschlag', $production),
                        'method' => 'GET',
                        'confirm' => null,
                    ] : null,
                ];
            }

            // 4. Packvorgang offen kurz vor/nach Start.
            if ($daysUntil <= 3 && ! $production->isComplete()) {
                $total = $production->packlistEntries()->count();
                $packed = $production->packedItemIds()->count();
                $findings[] = [
                    'severity' => 'gelb',
                    'kind' => 'pack_offen',
                    'title' => "{$production->bezeichnung}: Packen offen ({$packed}/{$total})",
                    'detail' => "Start {$when}",
                    'url' => route('packvorgang.show', $production),
                    'action' => null,
                ];
            }
        }

        usort($findings, fn ($a, $b) => ['rot' => 0, 'gelb' => 1][$a['severity']] <=> ['rot' => 0, 'gelb' => 1][$b['severity']]);

        return $findings;
    }
}
