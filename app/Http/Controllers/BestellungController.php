<?php

namespace App\Http\Controllers;

use App\Http\Requests\BestellungRequest;
use App\Models\Bestellung;
use App\Services\SlackBestellungSync;
use Illuminate\Http\Request;

class BestellungController extends Controller
{
    public function index(Request $request)
    {
        $status = in_array($request->query('status'), ['abgelegt', 'alle'], true) ? $request->query('status') : 'offen';
        $q = trim((string) $request->query('q', ''));

        $query = Bestellung::with('besteller')->orderByDesc('bestellt_am')->orderByDesc('id');

        if ($status === 'offen') {
            $query->offen();
        } elseif ($status === 'abgelegt') {
            $query->abgelegt();
        }

        if ($q !== '') {
            $like = '%'.addcslashes($q, '\\%_').'%';
            $query->where(function ($w) use ($like) {
                $w->where('bestellt_bei', 'like', $like)
                    ->orWhere('bestellt_was', 'like', $like)
                    ->orWhere('sendungsnummer', 'like', $like)
                    ->orWhereHas('besteller', fn ($b) => $b->where('name', 'like', $like));
            });
        }

        $bestellungen = $query->paginate(25)->withQueryString();

        return view('bestellungen.index', compact('bestellungen', 'status', 'q'));
    }

    public function show(Bestellung $bestellung)
    {
        $bestellung->load(['besteller', 'rechnungVon', 'abgelegtVon']);

        return view('bestellungen.show', compact('bestellung'));
    }

    public function edit(Bestellung $bestellung)
    {
        return view('bestellungen.edit', compact('bestellung'));
    }

    public function update(BestellungRequest $request, Bestellung $bestellung)
    {
        $bestellung->update($request->validated());
        $this->syncAfterResponse($bestellung);

        return redirect()->route('bestellungen.show', $bestellung)->with('success', 'Bestellung gespeichert.');
    }

    public function rechnung(Request $request, Bestellung $bestellung)
    {
        $bestellung->markRechnungVorhanden($request->user());
        $this->syncAfterResponse($bestellung);

        return redirect()->route('bestellungen.show', $bestellung)->with('success', 'Rechnung als vorliegend markiert.');
    }

    public function abgelegt(Request $request, Bestellung $bestellung)
    {
        abort_unless($request->user()->ist_buchhaltung, 403, 'Nur die Buchhaltung darf Bestellungen als bezahlt & abgelegt markieren.');

        $bestellung->markAbgelegt($request->user());
        $this->syncAfterResponse($bestellung);

        return redirect()->route('bestellungen.show', $bestellung)->with('success', 'Als bezahlt & abgelegt markiert.');
    }

    public function zuruecknehmen(Request $request, Bestellung $bestellung)
    {
        if ($bestellung->abgelegt_at) {
            abort_unless($request->user()->ist_buchhaltung, 403, 'Nur die Buchhaltung darf „Bezahlt & abgelegt“ zurücknehmen.');
        }

        $bestellung->revertStage($request->user());
        $this->syncAfterResponse($bestellung);

        return redirect()->route('bestellungen.show', $bestellung)->with('success', 'Stufe zurückgenommen.');
    }

    public function resync(Bestellung $bestellung)
    {
        if (! config('services.slack.bot_token') || ! SlackBestellungSync::channelFor($bestellung)) {
            return redirect()->route('bestellungen.show', $bestellung)
                ->with('error', 'Slack ist nicht eingerichtet: Bitte Bot-Token und Bestell-Kanal (Einstellungen) hinterlegen.');
        }

        $this->syncAfterResponse($bestellung);

        return redirect()->route('bestellungen.show', $bestellung)->with('success', 'Die Slack-Nachricht wird gesendet bzw. aktualisiert.');
    }

    /** Slack erst nach der Antwort bedienen, damit die Seite nicht auf Slack wartet. */
    private function syncAfterResponse(Bestellung $bestellung): void
    {
        dispatch(fn () => app(SlackBestellungSync::class)->sync($bestellung))->afterResponse();
    }
}
