<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkItemRequest;
use App\Models\Geraetetyp;
use App\Models\Item;
use App\Models\Mietvorgang;
use App\Models\Production;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\Vermietvorgang;
use App\Services\BulkItemService;
use App\Services\EquipmentMatcher;
use App\Services\ItemAssignmentService;
use App\Services\ProductionAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ItemBulkController extends Controller
{
    public function __construct(
        private BulkItemService $bulk,
        private EquipmentMatcher $matcher,
    ) {}

    public function create()
    {
        $units = Unit::ordered()->get();
        $types = Geraetetyp::orderedByUnit()->get()->map(fn ($t) => [
            'id' => $t->id, 'bezeichnung' => $t->bezeichnung, 'units_id' => $t->units_id,
        ]);
        $nextNumbers = $units->mapWithKeys(fn ($u) => [$u->id => $this->bulk->suggestNextNumber($u->id)]);

        return view('items.bulk-create', [
            'units' => $units,
            'types' => $types,
            'suppliers' => Supplier::orderBy('bezeichnung')->get(),
            'nextNumbers' => $nextNumbers,
        ]);
    }

    /** Freitext ("3x LDX #21-23, 2 Stativ") → vorbefüllte Zeilen. */
    public function parse(Request $request)
    {
        $request->validate(['text' => ['required', 'string', 'max:5000']]);

        $types = Geraetetyp::with('unit')->get();
        $units = Unit::all();
        $rows = [];

        foreach ($this->matcher->parseLines($request->text) as $line) {
            $match = $this->matcher->match($line['query'], $types, $units);
            $typId = $match['kind'] === 'typ' && count($match['geraetetyp_ids']) === 1 ? $match['geraetetyp_ids'][0] : null;
            $unitId = $match['unit_id'];

            $rows[] = [
                'geraetetyp_id' => $typId,
                'units_id' => $unitId,
                'bezeichnung' => $typId ? $types->firstWhere('id', $typId)->bezeichnung : $line['query'],
                'anzahl' => $line['anzahl'],
                'nummern' => $line['nummern'] ?? '',
                'matched' => (bool) $typId,
                'hint' => $match['kind'] === null
                    ? 'Kein Typ erkannt — Gruppe bitte wählen.'
                    : ($typId ? null : 'Mehrdeutig/Gruppe erkannt — Typ ggf. wählen.'),
            ];
        }

        return response()->json(['rows' => $rows]);
    }

    public function suggestNumber(Request $request)
    {
        $request->validate(['units_id' => ['required', 'exists:units,id']]);

        return response()->json(['nummer' => $this->bulk->suggestNextNumber((int) $request->units_id)]);
    }

    public function store(BulkItemRequest $request)
    {
        $result = $this->bulk->createMany($request->validated()['rows']);

        $count = count($result['created']);
        $message = "{$count} Gerät(e) angelegt.";
        $redirect = redirect()->route('items.index')->with($count ? 'success' : 'error', $message);

        if ($result['warnings']) {
            $redirect->with('warning', implode(' ', $result['warnings']));
        }

        return $redirect;
    }

    /** Massenaktion aus der Geräteliste. */
    public function action(
        Request $request,
        ProductionAssignmentService $productions,
        ItemAssignmentService $assign,
    ) {
        $data = $request->validate([
            'action' => ['required', Rule::in(['production', 'mietvorgang', 'vermietvorgang', 'unit'])],
            'item_ids' => ['required', 'array', 'min:1', 'max:500'],
            'item_ids.*' => ['integer', 'exists:items,id'],
            'target_id' => ['required', 'integer'],
        ]);

        $items = Item::whereIn('id', $data['item_ids'])->orderBy('bezeichnung')->orderBy('nummer')->get();
        $added = 0;
        $skipped = [];

        switch ($data['action']) {
            case 'production':
                $target = Production::findOrFail($data['target_id']);
                $result = $productions->attachMany($target, $items);
                $added = count($result['added']);
                foreach ($result['skipped'] as $s) {
                    $skipped[] = "{$s['item']->bezeichnung} {$s['item']->nummer} ({$s['reason']})";
                }
                $targetLabel = "Produktion „{$target->bezeichnung}“";
                break;

            case 'mietvorgang':
                $target = Mietvorgang::findOrFail($data['target_id']);
                foreach ($items as $item) {
                    $r = $assign->attachToMietvorgang($item, $target, manual: true, notifySlack: false);
                    $r['added'] ? $added++ : $skipped[] = "{$item->bezeichnung} {$item->nummer} (".($r['reason'] ?? 'bereits zugeordnet').')';
                }
                app(\App\Services\SlackVorgangSync::class)->syncMietvorgang($target);
                $targetLabel = 'Mietvorgang';
                break;

            case 'vermietvorgang':
                $target = Vermietvorgang::findOrFail($data['target_id']);
                foreach ($items as $item) {
                    $r = $assign->attachToVermietvorgang($item, $target, manual: true, notifySlack: false);
                    $r['added'] ? $added++ : $skipped[] = "{$item->bezeichnung} {$item->nummer} (".($r['reason'] ?? 'bereits zugeordnet').')';
                }
                app(\App\Services\SlackVorgangSync::class)->syncVermietvorgang($target);
                $targetLabel = 'Vermietvorgang';
                break;

            case 'unit':
                $target = Unit::findOrFail($data['target_id']);
                foreach ($items as $item) {
                    $item->update(['units_id' => $target->id]);
                    $added++;
                }
                $targetLabel = "Gruppe „{$target->bezeichnung}“";
                break;
        }

        $message = $data['action'] === 'unit'
            ? "{$added} Gerät(e) nach {$targetLabel} verschoben."
            : "{$added} Gerät(e) zu {$targetLabel} zugeordnet.";

        $redirect = redirect()->back()->with($added ? 'success' : 'error', $message);
        if ($skipped) {
            $redirect->with('warning', 'Übersprungen: '.implode(', ', $skipped));
        }

        return $redirect;
    }
}
