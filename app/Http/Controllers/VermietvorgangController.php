<?php

namespace App\Http\Controllers;

use App\Http\Requests\VermietvorgangRequest;
use App\Models\Item;
use App\Models\MailingList;
use App\Models\Mieter;
use App\Models\Vermietvorgang;
use App\Services\ItemAvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class VermietvorgangController extends Controller
{
    public function __construct(private ItemAvailabilityService $availability) {}

    public function index()
    {
        $vermietvorgaenge = Vermietvorgang::with('mieter')
            ->withCount('items')
            ->orderByDesc('created_at')
            ->get();

        return view('vermietvorgaenge.index', compact('vermietvorgaenge'));
    }

    public function create()
    {
        $mieter = Mieter::orderBy('bezeichnung')->get();
        $mailingLists = MailingList::orderBy('name')->get();
        $defaultMailingList = MailingList::where('is_default', true)->first();

        return view('vermietvorgaenge.create', compact('mieter', 'mailingLists', 'defaultMailingList'));
    }

    public function store(VermietvorgangRequest $request)
    {
        $data = $this->prepareData($request);

        Vermietvorgang::create($data);

        return redirect()->route('vermietvorgaenge.index')->with('success', 'Vermietvorgang angelegt.');
    }

    public function show(Vermietvorgang $vermietvorgang)
    {
        $vermietvorgang->load(['items', 'mieter', 'mailingList']);

        $mieter = Mieter::orderBy('bezeichnung')->get();
        $mailingLists = MailingList::orderBy('name')->get();
        $defaultMailingList = MailingList::where('is_default', true)->first();

        // Anders als bei Mietvorgang (wo nur bereits als Mietmaterial markierte
        // Geräte zur Auswahl stehen) geht es hier um eigenes Material, das noch
        // gar keinem Mieter zugeordnet sein muss — daher stehen alle Geräte zur
        // Wahl, die nicht bereits diesem Vermietvorgang zugeordnet sind, gefiltert
        // auf tatsächliche Verfügbarkeit im Verleihzeitraum.
        $assignableItems = Item::where(function ($q) use ($vermietvorgang) {
                $q->whereNull('vermietvorgang_id')->orWhere('vermietvorgang_id', '!=', $vermietvorgang->id);
            })
            ->orderBy('bezeichnung')
            ->get()
            ->filter(function (Item $item) use ($vermietvorgang) {
                return $this->availability->checkForVerleih(
                    $item,
                    $vermietvorgang->rent_start->format('Y-m-d'),
                    $vermietvorgang->rent_end->format('Y-m-d')
                )['available'];
            })
            ->values();

        return view('vermietvorgaenge.show', compact('vermietvorgang', 'mieter', 'mailingLists', 'defaultMailingList', 'assignableItems'));
    }

    public function update(VermietvorgangRequest $request, Vermietvorgang $vermietvorgang)
    {
        $data = $this->prepareData($request);

        $vermietvorgang->update($data);

        // Vermietvorgang bleibt führend: zugeordnete Geräte übernehmen Mieter/Zeitraum.
        $vermietvorgang->items()->update([
            'mieter_id' => $vermietvorgang->mieter_id,
            'verleih_start' => $vermietvorgang->rent_start,
            'verleih_end' => $vermietvorgang->rent_end,
        ]);

        return redirect()->route('vermietvorgaenge.show', $vermietvorgang)->with('success', 'Vermietvorgang aktualisiert.');
    }

    public function destroy(Vermietvorgang $vermietvorgang)
    {
        if ($vermietvorgang->items()->exists()) {
            return redirect()->route('vermietvorgaenge.index')
                ->with('error', 'Diesem Vermietvorgang sind noch Geräte zugeordnet. Bitte zuerst alle Geräte zurücksetzen (auf der Vermietvorgang-Detailseite).');
        }

        $vermietvorgang->delete();

        return redirect()->route('vermietvorgaenge.index')->with('success', 'Vermietvorgang gelöscht.');
    }

    public function attachItems(Request $request, Vermietvorgang $vermietvorgang)
    {
        $itemIds = collect((array) $request->input('item_id'))->filter()->unique()->values();

        $added = [];
        $skipped = [];

        Item::whereIn('id', $itemIds)->get()->each(function (Item $item) use ($vermietvorgang, &$added, &$skipped) {
            $check = $this->availability->checkForVerleih(
                $item,
                $vermietvorgang->rent_start->format('Y-m-d'),
                $vermietvorgang->rent_end->format('Y-m-d')
            );

            if (! $check['available']) {
                $skipped[] = "{$item->bezeichnung} ({$check['reason']})";

                return;
            }

            $item->update([
                'mieter_id' => $vermietvorgang->mieter_id,
                'verleih_start' => $vermietvorgang->rent_start,
                'verleih_end' => $vermietvorgang->rent_end,
                'vermietvorgang_id' => $vermietvorgang->id,
                'vermietvorgang_manual' => true,
            ]);

            activity('item')
                ->performedOn($item)
                ->event('attached')
                ->withProperties(['vermietvorgang_id' => $vermietvorgang->id])
                ->log("Gerät \"{$item->bezeichnung}\" dem Vermietvorgang ({$vermietvorgang->mieter->bezeichnung}) zugeordnet");

            $added[] = $item->bezeichnung;
        });

        $messageParts = [];
        if (count($added)) {
            $messageParts[] = count($added).' Gerät(e) zugeordnet: '.implode(', ', $added);
        }
        if (count($skipped)) {
            $messageParts[] = 'Übersprungen: '.implode(', ', $skipped);
        }

        $messageType = count($added) ? 'success' : 'error';
        $message = $messageParts ? implode(' — ', $messageParts) : 'Keine Geräte ausgewählt.';

        return redirect()->route('vermietvorgaenge.show', $vermietvorgang)->with($messageType, $message);
    }

    public function detachItem(Vermietvorgang $vermietvorgang, Item $item)
    {
        activity('item')
            ->performedOn($item)
            ->event('detached')
            ->withProperties(['vermietvorgang_id' => $vermietvorgang->id])
            ->log("Gerät \"{$item->bezeichnung}\" aus Vermietvorgang ({$vermietvorgang->mieter->bezeichnung}) entfernt");

        $item->removeFromVermietvorgang();

        return redirect()->route('vermietvorgaenge.show', $vermietvorgang)->with('success', 'Gerät entfernt.');
    }

    public function confirmTransport(Vermietvorgang $vermietvorgang, string $type)
    {
        abort_unless(in_array($type, ['start', 'end']), 404);

        $vermietvorgang->update([
            "transport_{$type}_confirmed_at" => now(),
            "transport_{$type}_confirmed_by" => auth()->id(),
        ]);

        $label = $vermietvorgang->transportActionLabel($type);
        $this->logConfirmation($vermietvorgang, $label, true);

        return redirect()->back()->with('success', 'Als '.mb_strtolower($label).' markiert.');
    }

    public function reopenTransport(Vermietvorgang $vermietvorgang, string $type)
    {
        abort_unless(in_array($type, ['start', 'end']), 404);

        $vermietvorgang->update([
            "transport_{$type}_confirmed_at" => null,
            "transport_{$type}_confirmed_by" => null,
        ]);

        $label = $vermietvorgang->transportActionLabel($type);
        $this->logConfirmation($vermietvorgang, $label, false);

        return redirect()->back()->with('success', 'Wieder geöffnet.');
    }

    public function confirmGerichtet(Vermietvorgang $vermietvorgang)
    {
        $vermietvorgang->update([
            'gerichtet_confirmed_at' => now(),
            'gerichtet_confirmed_by' => auth()->id(),
        ]);

        $this->logConfirmation($vermietvorgang, 'Gerichtet', true);

        return redirect()->back()->with('success', 'Als gerichtet markiert.');
    }

    public function reopenGerichtet(Vermietvorgang $vermietvorgang)
    {
        $vermietvorgang->update([
            'gerichtet_confirmed_at' => null,
            'gerichtet_confirmed_by' => null,
        ]);

        $this->logConfirmation($vermietvorgang, 'Gerichtet', false);

        return redirect()->back()->with('success', 'Wieder geöffnet.');
    }

    public function confirmVollstaendigZurueck(Vermietvorgang $vermietvorgang)
    {
        $vermietvorgang->update([
            'vollstaendig_zurueck_confirmed_at' => now(),
            'vollstaendig_zurueck_confirmed_by' => auth()->id(),
        ]);

        $this->logConfirmation($vermietvorgang, 'Vollständig zurück', true);

        return redirect()->back()->with('success', 'Als vollständig zurück markiert.');
    }

    public function reopenVollstaendigZurueck(Vermietvorgang $vermietvorgang)
    {
        $vermietvorgang->update([
            'vollstaendig_zurueck_confirmed_at' => null,
            'vollstaendig_zurueck_confirmed_by' => null,
        ]);

        $this->logConfirmation($vermietvorgang, 'Vollständig zurück', false);

        return redirect()->back()->with('success', 'Wieder geöffnet.');
    }

    private function logConfirmation(Vermietvorgang $vermietvorgang, string $label, bool $confirmed): void
    {
        $mieter = $vermietvorgang->mieter?->bezeichnung ?? 'unbekannter Mieter';

        activity('vermietvorgang')
            ->performedOn($vermietvorgang)
            ->causedBy(auth()->user())
            ->event($confirmed ? 'confirmed' : 'reopened')
            ->log(($confirmed ? "{$label} über die Anwendung als geklärt markiert" : "{$label} wieder geöffnet")." (Mieter: {$mieter})");
    }

    private function prepareData(VermietvorgangRequest $request): array
    {
        $data = [
            'transport_type_start' => $request->validated('transport_type_start'),
            'transport_type_end' => $request->validated('transport_type_end'),
            'notify_mieter' => $request->boolean('notify_mieter'),
            'reminder_days_before_start' => $request->reminder_days_before_start ?: null,
            'reminder_days_before_end' => $request->reminder_days_before_end ?: null,
            'mailing_list_id' => $request->mailing_list_id ?: null,
        ];

        if ($request->filled('mieter_id')) {
            $data['mieter_id'] = $request->mieter_id;
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
