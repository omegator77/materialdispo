<?php

namespace App\Http\Controllers;

use App\Models\Mietvorgang;
use App\Models\Vermietvorgang;
use App\Services\ItemAssignmentService;
use App\Services\SlackVorgangSync;
use App\Services\SmartAssignmentService;
use Illuminate\Http\Request;

/**
 * Schnell-Hinzufügen per Freitext für Miet- und Vermietvorgänge — dieselbe
 * Bedarfs-Logik wie bei Produktionen, mit den Verfügbarkeitsregeln des Vorgangs.
 */
class VorgangQuickAddController extends Controller
{
    public function __construct(
        private SmartAssignmentService $smart,
        private ItemAssignmentService $assign,
        private SlackVorgangSync $slack,
    ) {}

    public function previewMiete(Request $request, Mietvorgang $mietvorgang)
    {
        return $this->preview($request, $mietvorgang);
    }

    public function previewVerleih(Request $request, Vermietvorgang $vermietvorgang)
    {
        return $this->preview($request, $vermietvorgang);
    }

    public function storeMiete(Request $request, Mietvorgang $mietvorgang)
    {
        $added = $this->store($request, $mietvorgang);
        $this->slack->syncMietvorgang($mietvorgang);

        return $this->done(route('mietvorgaenge.show', $mietvorgang), $added);
    }

    public function storeVerleih(Request $request, Vermietvorgang $vermietvorgang)
    {
        $added = $this->store($request, $vermietvorgang);
        $this->slack->syncVermietvorgang($vermietvorgang);

        return $this->done(route('vermietvorgaenge.show', $vermietvorgang), $added);
    }

    private function preview(Request $request, Mietvorgang|Vermietvorgang $vorgang)
    {
        $request->validate(['text' => ['required', 'string', 'max:2000']]);
        $plan = $this->smart->planForVorgang($vorgang, $this->smart->parseNeeds($request->text));

        return response()->json($this->smart->serializePlan($plan));
    }

    /** @return array{added: string[], missing: int} */
    private function store(Request $request, Mietvorgang|Vermietvorgang $vorgang): array
    {
        $request->validate(['text' => ['required', 'string', 'max:2000']]);
        $plan = $this->smart->planForVorgang($vorgang, $this->smart->parseNeeds($request->text));

        $added = [];
        foreach ($plan['items'] as $item) {
            $result = $vorgang instanceof Mietvorgang
                ? $this->assign->attachToMietvorgang($item, $vorgang, manual: true, notifySlack: false)
                : $this->assign->attachToVermietvorgang($item, $vorgang, manual: true, notifySlack: false);
            if ($result['added']) {
                $added[] = trim($item->bezeichnung.' '.$item->nummer);
            }
        }

        return ['added' => $added, 'missing' => $plan['missing']];
    }

    private function done(string $url, array $r)
    {
        $parts = [];
        if ($r['added']) {
            $parts[] = count($r['added']).' Gerät(e) zugeordnet: '.implode(', ', $r['added']);
        }
        if ($r['missing']) {
            $parts[] = $r['missing'].' Position(en) konnten nicht gedeckt werden.';
        }

        return redirect($url)->with($r['added'] ? 'success' : 'error', implode(' — ', $parts) ?: 'Nichts zu tun.');
    }
}
