<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Production;
use App\Services\SmartAssignmentService;
use Illuminate\Http\Request;

/**
 * Schnell-Hinzufügen per Freitext, Auto-Zuordnung offener VB-Bedarfe und
 * Konflikt-Austausch einer Produktion.
 */
class SmartProductionController extends Controller
{
    public function __construct(private SmartAssignmentService $smart) {}

    public function preview(Request $request, Production $production)
    {
        $request->validate(['text' => ['required', 'string', 'max:2000']]);

        $plan = $this->smart->plan($production, $this->smart->parseNeeds($request->text));

        return response()->json($this->smart->serializePlan($plan));
    }

    public function store(Request $request, Production $production)
    {
        $request->validate(['text' => ['required', 'string', 'max:2000']]);

        $plan = $this->smart->plan($production, $this->smart->parseNeeds($request->text));

        return $this->applyAndRedirect($production, $plan);
    }

    public function autoFill(Production $production)
    {
        $needs = $this->smart->openVbNeeds($production);

        if (! $needs) {
            return redirect()->back()->with('success', 'Alle Anforderungen aus dem VB-Protokoll sind bereits abgedeckt.');
        }

        return $this->applyAndRedirect($production, $this->smart->plan($production, $needs));
    }

    public function replaceItem(Production $production, Item $item)
    {
        if (! $production->items()->where('items.id', $item->id)->exists()) {
            return redirect()->back()->with('error', 'Das Gerät gehört nicht (mehr) zu dieser Produktion.');
        }

        $new = $this->smart->replaceItem($production, $item);

        return $new
            ? redirect()->back()->with('success', "„{$item->bezeichnung} {$item->nummer}“ durch „{$new->bezeichnung} {$new->nummer}“ ersetzt.")
            : redirect()->back()->with('error', 'Kein freies Ersatzgerät gefunden.');
    }

    private function applyAndRedirect(Production $production, array $plan)
    {
        $result = $this->smart->apply($production, $plan);
        $added = count($result['added']);

        $parts = [];
        if ($added) {
            $parts[] = $added.' Gerät(e) hinzugefügt: '.collect($result['added'])->map(fn ($i) => trim($i->bezeichnung.' '.$i->nummer))->implode(', ');
        }
        if ($plan['missing']) {
            $parts[] = $plan['missing'].' Position(en) konnten nicht gedeckt werden.';
        }
        foreach ($result['skipped'] as $s) {
            $parts[] = "{$s['item']->bezeichnung} ({$s['reason']})";
        }

        return redirect()->route('productions.show', $production)
            ->with($added ? 'success' : 'error', implode(' — ', $parts) ?: 'Nichts zu tun.');
    }
}
