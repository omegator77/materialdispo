<?php

namespace App\Http\Controllers;

use App\Http\Requests\MietvorgangRequest;
use App\Models\Item;
use App\Models\MailingList;
use App\Models\Mietvorgang;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MietvorgangController extends Controller
{
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

        return view('mietvorgaenge.create', compact('suppliers', 'mailingLists', 'defaultMailingList'));
    }

    public function store(MietvorgangRequest $request)
    {
        $data = $this->prepareData($request);

        Mietvorgang::create($data);

        return redirect()->route('mietvorgaenge.index')->with('success', 'Mietvorgang angelegt.');
    }

    public function show(Mietvorgang $mietvorgang)
    {
        $mietvorgang->load(['items', 'supplier', 'mailingList']);

        $suppliers = Supplier::orderBy('bezeichnung')->get();
        $mailingLists = MailingList::orderBy('name')->get();
        $defaultMailingList = MailingList::where('is_default', true)->first();

        $assignableItems = Item::whereNotNull('suppliers_id')
            ->where(function ($q) use ($mietvorgang) {
                $q->whereNull('mietvorgang_id')->orWhere('mietvorgang_id', '!=', $mietvorgang->id);
            })
            ->orderBy('bezeichnung')
            ->get();

        return view('mietvorgaenge.show', compact('mietvorgang', 'suppliers', 'mailingLists', 'defaultMailingList', 'assignableItems'));
    }

    public function update(MietvorgangRequest $request, Mietvorgang $mietvorgang)
    {
        $data = $this->prepareData($request);

        $mietvorgang->update($data);

        // Mietvorgang bleibt führend: zugeordnete Geräte übernehmen Vermieter/Zeitraum.
        $mietvorgang->items()->update([
            'suppliers_id' => $mietvorgang->suppliers_id,
            'rent_start' => $mietvorgang->rent_start,
            'rent_end' => $mietvorgang->rent_end,
        ]);

        return redirect()->route('mietvorgaenge.show', $mietvorgang)->with('success', 'Mietvorgang aktualisiert.');
    }

    public function destroy(Mietvorgang $mietvorgang)
    {
        if ($mietvorgang->items()->exists()) {
            return redirect()->route('mietvorgaenge.index')
                ->with('error', 'Diesem Mietvorgang sind noch Geräte zugeordnet. Bitte zuerst alle Geräte zurücksetzen (auf der Mietvorgang-Detailseite).');
        }

        $mietvorgang->delete();

        return redirect()->route('mietvorgaenge.index')->with('success', 'Mietvorgang gelöscht.');
    }

    public function attachItems(Request $request, Mietvorgang $mietvorgang)
    {
        $itemIds = collect((array) $request->input('item_id'))->filter()->unique()->values();

        Item::whereIn('id', $itemIds)->get()->each(function (Item $item) use ($mietvorgang) {
            $item->update([
                'suppliers_id' => $mietvorgang->suppliers_id,
                'rent_start' => $mietvorgang->rent_start,
                'rent_end' => $mietvorgang->rent_end,
                'mietvorgang_id' => $mietvorgang->id,
                'mietvorgang_manual' => true,
            ]);

            activity('item')
                ->performedOn($item)
                ->event('attached')
                ->withProperties(['mietvorgang_id' => $mietvorgang->id])
                ->log("Gerät \"{$item->bezeichnung}\" dem Mietvorgang ({$mietvorgang->supplier->bezeichnung}) zugeordnet");
        });

        return redirect()->route('mietvorgaenge.show', $mietvorgang)->with('success', 'Geräte zugeordnet.');
    }

    public function detachItem(Mietvorgang $mietvorgang, Item $item)
    {
        activity('item')
            ->performedOn($item)
            ->event('detached')
            ->withProperties(['mietvorgang_id' => $mietvorgang->id])
            ->log("Gerät \"{$item->bezeichnung}\" aus Mietvorgang ({$mietvorgang->supplier->bezeichnung}) entfernt");

        $item->removeFromMietvorgang();

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

        return redirect()->back()->with('success', 'Wieder geöffnet.');
    }

    public function confirmKontrolliert(Mietvorgang $mietvorgang)
    {
        $mietvorgang->update([
            'kontrolliert_confirmed_at' => now(),
            'kontrolliert_confirmed_by' => auth()->id(),
        ]);

        $this->logConfirmation($mietvorgang, 'Entgegengenommen und kontrolliert', true);

        return redirect()->back()->with('success', 'Als entgegengenommen und kontrolliert markiert.');
    }

    public function reopenKontrolliert(Mietvorgang $mietvorgang)
    {
        $mietvorgang->update([
            'kontrolliert_confirmed_at' => null,
            'kontrolliert_confirmed_by' => null,
        ]);

        $this->logConfirmation($mietvorgang, 'Entgegengenommen und kontrolliert', false);

        return redirect()->back()->with('success', 'Wieder geöffnet.');
    }

    public function confirmBereitZurRueckgabe(Mietvorgang $mietvorgang)
    {
        $mietvorgang->update([
            'bereit_zur_rueckgabe_confirmed_at' => now(),
            'bereit_zur_rueckgabe_confirmed_by' => auth()->id(),
        ]);

        $this->logConfirmation($mietvorgang, 'Bereit zur Rückgabe', true);

        return redirect()->back()->with('success', 'Als bereit zur Rückgabe markiert.');
    }

    public function reopenBereitZurRueckgabe(Mietvorgang $mietvorgang)
    {
        $mietvorgang->update([
            'bereit_zur_rueckgabe_confirmed_at' => null,
            'bereit_zur_rueckgabe_confirmed_by' => null,
        ]);

        $this->logConfirmation($mietvorgang, 'Bereit zur Rückgabe', false);

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
