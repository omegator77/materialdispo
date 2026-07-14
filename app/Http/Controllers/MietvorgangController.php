<?php

namespace App\Http\Controllers;

use App\Http\Requests\MietvorgangRequest;
use App\Models\Item;
use App\Models\MailingList;
use App\Models\Mietvorgang;
use App\Models\Supplier;
use App\Services\ItemAssignmentService;
use App\Services\ItemAvailabilityService;
use App\Services\SlackVorgangSync;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MietvorgangController extends Controller
{
    public function __construct(
        private ItemAvailabilityService $availability,
        private ItemAssignmentService $assign,
        private SlackVorgangSync $slack,
    ) {}

    /**
     * Liefert den Bezeichnungs-Vorschlag für einen Vermieter, damit das
     * Create-Formular ihn live einblenden kann, sobald ein Vermieter gewählt
     * wird — ohne dass der Vorgang dafür schon existieren muss.
     */
    public function suggestBezeichnung(Request $request)
    {
        $request->validate(['suppliers_id' => ['required', 'exists:suppliers,id']]);

        return response()->json([
            'bezeichnung' => Mietvorgang::suggestBezeichnung((int) $request->suppliers_id),
        ]);
    }

    public function index()
    {
        $mietvorgaenge = Mietvorgang::with('supplier')
            ->withCount('items')
            ->orderByDesc('created_at')
            ->get();

        return view('mietvorgaenge.index', compact('mietvorgaenge'));
    }

    public function create()
    {
        $suppliers = Supplier::orderBy('bezeichnung')->get();
        $mailingLists = MailingList::orderBy('name')->get();
        $defaultMailingList = MailingList::where('is_default', true)->first();
        $assignableItems = Item::whereNotNull('suppliers_id')
            ->orderBy('bezeichnung')
            ->get();

        return view('mietvorgaenge.create', compact('suppliers', 'mailingLists', 'defaultMailingList', 'assignableItems'));
    }

    public function store(MietvorgangRequest $request)
    {
        $data = $this->prepareData($request);
        $data['bezeichnung'] = $request->filled('bezeichnung')
            ? $request->bezeichnung
            : Mietvorgang::suggestBezeichnung($data['suppliers_id']);

        $mietvorgang = Mietvorgang::create($data);

        $itemIds = collect((array) $request->input('item_id'))->filter()->unique()->values()->all();
        if ($itemIds) {
            [, $message] = $this->attachItemIds($mietvorgang, $itemIds);

            return redirect()->route('mietvorgaenge.index')->with('success', 'Mietvorgang angelegt. '.$message);
        }

        return redirect()->route('mietvorgaenge.index')->with('success', 'Mietvorgang angelegt.');
    }

    public function show(Mietvorgang $mietvorgang)
    {
        $mietvorgang->load(['items', 'supplier', 'mailingList']);

        $suppliers = Supplier::orderBy('bezeichnung')->get();
        $mailingLists = MailingList::orderBy('name')->get();
        $defaultMailingList = MailingList::where('is_default', true)->first();

        $assignableItems = Item::whereNotNull('suppliers_id')
            ->whereDoesntHave('mietvorgaenge', fn ($q) => $q->where('mietvorgaenge.id', $mietvorgang->id))
            ->with(['mietvorgaenge', 'vermietvorgaenge'])
            ->orderBy('bezeichnung')
            ->get()
            ->filter(fn (Item $item) => $this->availability->checkForMiete($item, $mietvorgang)['available'])
            ->values();

        return view('mietvorgaenge.show', compact('mietvorgang', 'suppliers', 'mailingLists', 'defaultMailingList', 'assignableItems'));
    }

    public function pdf(Mietvorgang $mietvorgang)
    {
        $mietvorgang->load(['items.unit', 'supplier']);

        $itemsByUnit = $mietvorgang->items->groupBy(fn (Item $item) => $item->unit->bezeichnung ?? 'Ohne Gruppe');

        $pdf = Pdf::loadView('pdf.mietvorgang_materialliste', compact('mietvorgang', 'itemsByUnit'));

        return $pdf->download("Materialliste {$mietvorgang->bezeichnung}.pdf");
    }

    public function update(MietvorgangRequest $request, Mietvorgang $mietvorgang)
    {
        $data = $this->prepareData($request);

        if ($request->filled('bezeichnung')) {
            $data['bezeichnung'] = $request->bezeichnung;
        }

        $mietvorgang->update($data);

        $this->slack->syncMietvorgang($mietvorgang);

        return redirect()->route('mietvorgaenge.show', $mietvorgang)->with('success', 'Mietvorgang aktualisiert.');
    }

    public function destroy(Request $request, Mietvorgang $mietvorgang)
    {
        if ($mietvorgang->items()->exists()) {
            if (! $request->boolean('force')) {
                return redirect()->route('mietvorgaenge.index')
                    ->with('error', 'Diesem Mietvorgang sind noch Geräte zugeordnet. Bitte zuerst alle Geräte zurücksetzen (auf der Mietvorgang-Detailseite).');
            }

            $mietvorgang->items->each(function (Item $item) use ($mietvorgang) {
                activity('item')
                    ->performedOn($item)
                    ->event('detached')
                    ->withProperties(['mietvorgang_id' => $mietvorgang->id])
                    ->log("Gerät \"{$item->bezeichnung}\" aus Mietvorgang ({$mietvorgang->supplier->bezeichnung}) entfernt (Vorgang gelöscht)");

                $this->assign->detachFromMietvorgang($item, $mietvorgang, notifySlack: false);
            });
        }

        $mietvorgang->delete();

        return redirect()->route('mietvorgaenge.index')->with('success', 'Mietvorgang gelöscht.');
    }

    public function attachItems(Request $request, Mietvorgang $mietvorgang)
    {
        $itemIds = collect((array) $request->input('item_id'))->filter()->unique()->values()->all();
        [$added, $message] = $this->attachItemIds($mietvorgang, $itemIds);

        $messageType = $added ? 'success' : 'error';

        return redirect()->route('mietvorgaenge.show', $mietvorgang)->with($messageType, $message);
    }

    /**
     * Ordnet die übergebenen Geräte dem Mietvorgang zu (unter Berücksichtigung
     * der Verfügbarkeit im Mietzeitraum) — von attachItems() (bestehender
     * Vorgang) und store() (direkt bei Neuanlage) genutzt. Gibt [Anzahl
     * zugeordneter Geräte, Zusammenfassungstext] zurück.
     *
     * @param  array<int, int|string>  $itemIds
     * @return array{0: int, 1: string}
     */
    private function attachItemIds(Mietvorgang $mietvorgang, array $itemIds): array
    {
        $added = [];
        $skipped = [];

        Item::whereIn('id', $itemIds)->get()->each(function (Item $item) use ($mietvorgang, &$added, &$skipped) {
            $result = $this->assign->attachToMietvorgang($item, $mietvorgang, manual: true, notifySlack: false);

            if ($result['alreadyAttached']) {
                $skipped[] = "{$item->bezeichnung} (bereits zugeordnet)";
            } elseif (! $result['added']) {
                $skipped[] = "{$item->bezeichnung} ({$result['reason']})";
            } else {
                $added[] = $item->bezeichnung;
            }
        });

        $messageParts = [];
        if (count($added)) {
            $messageParts[] = count($added).' Gerät(e) zugeordnet: '.implode(', ', $added);
        }
        if (count($skipped)) {
            $messageParts[] = 'Übersprungen: '.implode(', ', $skipped);
        }

        if (count($added)) {
            $this->slack->syncMietvorgang($mietvorgang);
        }

        return [count($added), $messageParts ? implode(' — ', $messageParts) : 'Keine Geräte ausgewählt.'];
    }

    public function detachItem(Mietvorgang $mietvorgang, Item $item)
    {
        activity('item')
            ->performedOn($item)
            ->event('detached')
            ->withProperties(['mietvorgang_id' => $mietvorgang->id])
            ->log("Gerät \"{$item->bezeichnung}\" aus Mietvorgang ({$mietvorgang->supplier->bezeichnung}) entfernt");

        $this->assign->detachFromMietvorgang($item, $mietvorgang);

        return redirect()->route('mietvorgaenge.show', $mietvorgang)->with('success', 'Gerät entfernt.');
    }

    public function confirmTransport(Mietvorgang $mietvorgang, string $type)
    {
        abort_unless(in_array($type, ['start', 'end']), 404);

        $mietvorgang->update([
            "transport_{$type}_confirmed_at" => now(),
            "transport_{$type}_confirmed_by" => auth()->id(),
        ]);

        $label = $mietvorgang->transportActionLabel($type);
        $this->logConfirmation($mietvorgang, $label, true);
        $this->slack->syncMietvorgang($mietvorgang);

        return redirect()->back()->with('success', 'Als '.mb_strtolower($label).' markiert.');
    }

    public function reopenTransport(Mietvorgang $mietvorgang, string $type)
    {
        abort_unless(in_array($type, ['start', 'end']), 404);

        $mietvorgang->update([
            "transport_{$type}_confirmed_at" => null,
            "transport_{$type}_confirmed_by" => null,
        ]);

        $label = $mietvorgang->transportActionLabel($type);
        $this->logConfirmation($mietvorgang, $label, false);
        $this->slack->syncMietvorgang($mietvorgang);

        return redirect()->back()->with('success', 'Wieder geöffnet.');
    }

    public function confirmKontrolliert(Mietvorgang $mietvorgang)
    {
        $mietvorgang->update([
            'kontrolliert_confirmed_at' => now(),
            'kontrolliert_confirmed_by' => auth()->id(),
        ]);

        $this->logConfirmation($mietvorgang, 'Entgegengenommen und kontrolliert', true);
        $this->slack->syncMietvorgang($mietvorgang);

        return redirect()->back()->with('success', 'Als entgegengenommen und kontrolliert markiert.');
    }

    public function reopenKontrolliert(Mietvorgang $mietvorgang)
    {
        $mietvorgang->update([
            'kontrolliert_confirmed_at' => null,
            'kontrolliert_confirmed_by' => null,
        ]);

        $this->logConfirmation($mietvorgang, 'Entgegengenommen und kontrolliert', false);
        $this->slack->syncMietvorgang($mietvorgang);

        return redirect()->back()->with('success', 'Wieder geöffnet.');
    }

    public function confirmBereitZurRueckgabe(Mietvorgang $mietvorgang)
    {
        $mietvorgang->update([
            'bereit_zur_rueckgabe_confirmed_at' => now(),
            'bereit_zur_rueckgabe_confirmed_by' => auth()->id(),
        ]);

        $this->logConfirmation($mietvorgang, 'Bereit zur Rückgabe', true);
        $this->slack->syncMietvorgang($mietvorgang);

        return redirect()->back()->with('success', 'Als bereit zur Rückgabe markiert.');
    }

    public function reopenBereitZurRueckgabe(Mietvorgang $mietvorgang)
    {
        $mietvorgang->update([
            'bereit_zur_rueckgabe_confirmed_at' => null,
            'bereit_zur_rueckgabe_confirmed_by' => null,
        ]);

        $this->logConfirmation($mietvorgang, 'Bereit zur Rückgabe', false);
        $this->slack->syncMietvorgang($mietvorgang);

        return redirect()->back()->with('success', 'Wieder geöffnet.');
    }

    private function logConfirmation(Mietvorgang $mietvorgang, string $label, bool $confirmed): void
    {
        $supplier = $mietvorgang->supplier?->bezeichnung ?? 'unbekannter Vermieter';

        activity('mietvorgang')
            ->performedOn($mietvorgang)
            ->causedBy(auth()->user())
            ->event($confirmed ? 'confirmed' : 'reopened')
            ->log(($confirmed ? "{$label} über die Anwendung als geklärt markiert" : "{$label} wieder geöffnet")." (Vermieter: {$supplier})");
    }

    private function prepareData(MietvorgangRequest $request): array
    {
        $data = [
            'transport_type_start' => $request->validated('transport_type_start'),
            'transport_type_end' => $request->validated('transport_type_end'),
            'notify_supplier' => $request->boolean('notify_supplier'),
            'reminder_days_before_start' => $request->reminder_days_before_start ?: null,
            'reminder_days_before_end' => $request->reminder_days_before_end ?: null,
            'mailing_list_id' => $request->mailing_list_id ?: null,
        ];

        if ($request->filled('suppliers_id')) {
            $data['suppliers_id'] = $request->suppliers_id;
        }

        if ($request->filled('rent_start')) {
            $data['rent_start'] = Carbon::createFromFormat('d.m.Y', $request->rent_start)->format('Y-m-d');
        }

        if ($request->filled('rent_end')) {
            $data['rent_end'] = Carbon::createFromFormat('d.m.Y', $request->rent_end)->format('Y-m-d');
        }

        return $data;
    }
}
