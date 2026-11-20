<?php

namespace App\Http\Controllers;

use App\Http\Requests\VbProtokollRequest;
use App\Models\Geraetetyp;
use App\Models\Production;
use App\Models\Unit;
use App\Models\VbProtokoll;
use App\Models\VbProtokollFoto;
use App\Services\SlackVorgangSync;
use App\Services\VbProtokollAnforderungSyncService;
use App\Services\VbProtokollFreitextBlockSyncService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VbProtokollController extends Controller
{
    public function __construct(
        private VbProtokollAnforderungSyncService $anforderungen,
        private VbProtokollFreitextBlockSyncService $freitextBloecke,
        private SlackVorgangSync $slack,
    ) {}

    public function create(Production $production)
    {
        if ($production->vbProtokoll) {
            return redirect()->route('vb-protokoll.edit', $production->id);
        }

        $units = Unit::ordered()->get();
        $geraetetypen = Geraetetyp::orderedByUnit()->get();
        $templates = $this->templateCandidates($production);

        return view('vb-protokoll.create', compact('production', 'units', 'geraetetypen', 'templates'));
    }

    /** Produktionen mit VB-Protokoll — ähnlichste (Name) zuerst, dann neueste. */
    private function templateCandidates(Production $production)
    {
        $matcher = app(\App\Services\EquipmentMatcher::class);

        return Production::whereHas('vbProtokoll.anforderungen')
            ->where('id', '!=', $production->id)
            ->orderByDesc('booking_start')
            ->limit(30)
            ->get()
            ->sortByDesc(fn (Production $p) => $matcher->score($production->bezeichnung, $p->bezeichnung))
            ->take(8)
            ->values();
    }

    /**
     * Neues Protokoll aus einer früheren Produktion: Bedarf (Anforderungen),
     * Freitext-Blöcke und Crew-Stärken werden übernommen — Kunde und Ort
     * sind veranstaltungsspezifisch und bleiben leer.
     */
    public function fromTemplate(Production $production, Production $source)
    {
        if ($production->vbProtokoll()->exists()) {
            return redirect()->route('vb-protokoll.edit', $production->id);
        }

        $template = $source->vbProtokoll()->with(['anforderungen', 'freitextBloecke'])->first();
        if (! $template) {
            return redirect()->back()->with('error', "„{$source->bezeichnung}“ hat kein VB-Protokoll.");
        }

        $vb = $production->vbProtokoll()->create(array_merge(
            collect($template->only(array_filter((new VbProtokoll)->getFillable(), fn ($f) => str_starts_with($f, 'crew_'))))->all(),
            ['created_by' => auth()->id()]
        ));

        foreach ($template->anforderungen as $a) {
            $vb->anforderungen()->create(collect($a->only($a->getFillable()))->except('vb_protokoll_id')->all());
        }
        foreach ($template->freitextBloecke as $b) {
            $vb->freitextBloecke()->create(['ueberschrift' => $b->ueberschrift, 'text' => $b->text, 'sort_order' => $b->sort_order]);
        }

        $this->slack->syncProduction($production);

        return redirect()->route('vb-protokoll.edit', $production->id)
            ->with('success', "Protokoll aus „{$source->bezeichnung}“ übernommen — bitte Kunde/Ort ergänzen und Bedarf prüfen.");
    }

    public function store(VbProtokollRequest $request, Production $production)
    {
        if ($production->vbProtokoll()->exists()) {
            return redirect()->route('vb-protokoll.edit', $production->id);
        }

        $vbProtokoll = $production->vbProtokoll()->create(array_merge(
            $request->fields(),
            ['created_by' => auth()->id()]
        ));

        $this->anforderungen->sync($vbProtokoll, $request->anforderungenInput());
        $this->freitextBloecke->sync($vbProtokoll, $request->freitextBloeckeInput());
        $this->storeFotos($vbProtokoll, $request);

        $this->slack->syncProduction($production);

        return redirect()
            ->route('vb-protokoll.show', $production->id)
            ->with('success', 'VB-Protokoll erfolgreich angelegt.');
    }

    public function show(Production $production)
    {
        $vbProtokoll = $production->vbProtokoll()->with(['anforderungen.unit', 'anforderungen.geraetetyp', 'freitextBloecke', 'fotos', 'creator'])->firstOrFail();

        return view('vb-protokoll.show', compact('production', 'vbProtokoll'));
    }

    public function edit(Production $production)
    {
        $vbProtokoll = $production->vbProtokoll()->with(['anforderungen', 'freitextBloecke', 'fotos'])->firstOrFail();
        $units = Unit::ordered()->get();
        $geraetetypen = Geraetetyp::orderedByUnit()->get();

        return view('vb-protokoll.edit', compact('production', 'vbProtokoll', 'units', 'geraetetypen'));
    }

    public function update(VbProtokollRequest $request, Production $production)
    {
        $vbProtokoll = $production->vbProtokoll()->firstOrFail();

        $vbProtokoll->update($request->fields());

        $this->anforderungen->sync($vbProtokoll, $request->anforderungenInput());
        $this->freitextBloecke->sync($vbProtokoll, $request->freitextBloeckeInput());
        $this->storeFotos($vbProtokoll, $request);

        $this->slack->syncProduction($production);

        return redirect()
            ->route('vb-protokoll.show', $production->id)
            ->with('success', 'VB-Protokoll erfolgreich aktualisiert.');
    }

    public function generatePDF(Production $production)
    {
        return $this->renderPdf($production, showAbgleich: false);
    }

    public function generateAbgleichReportPDF(Production $production)
    {
        return $this->renderPdf($production, showAbgleich: true);
    }

    private function renderPdf(Production $production, bool $showAbgleich)
    {
        $vbProtokoll = $production->vbProtokoll()
            ->with(['anforderungen.unit', 'anforderungen.geraetetyp', 'freitextBloecke', 'fotos', 'creator'])
            ->firstOrFail();

        $fotoPaths = $vbProtokoll->fotos->map(
            fn (VbProtokollFoto $foto) => Storage::disk('public')->path($foto->path)
        );

        $pdf = Pdf::loadView('pdf.vb_protokoll', compact('production', 'vbProtokoll', 'fotoPaths', 'showAbgleich'));

        $suffix = $showAbgleich ? ' Abgleich' : '';

        return $pdf->download("VB-Protokoll {$production->bezeichnung}{$suffix}.pdf");
    }

    public function destroy(Production $production)
    {
        $vbProtokoll = $production->vbProtokoll()->with('fotos')->firstOrFail();

        foreach ($vbProtokoll->fotos as $foto) {
            Storage::disk('public')->delete($foto->path);
        }

        $vbProtokoll->delete();

        $this->slack->syncProduction($production);

        return redirect()
            ->route('productions.show', $production->id)
            ->with('success', 'VB-Protokoll gelöscht.');
    }

    public function destroyFoto(VbProtokollFoto $foto)
    {
        $productionId = $foto->vbProtokoll->production_id;

        Storage::disk('public')->delete($foto->path);
        $foto->delete();

        return redirect()
            ->route('vb-protokoll.edit', $productionId)
            ->with('success', 'Foto gelöscht.');
    }

    private function storeFotos(VbProtokoll $vbProtokoll, Request $request): void
    {
        if (! $request->hasFile('fotos')) {
            return;
        }

        foreach ($request->file('fotos') as $foto) {
            if (! $foto || ! $foto->isValid()) {
                continue;
            }

            $path = $foto->store('vb-protokoll-fotos', 'public');

            $vbProtokoll->fotos()->create([
                'path' => $path,
                'original_name' => $foto->getClientOriginalName(),
            ]);
        }
    }
}
