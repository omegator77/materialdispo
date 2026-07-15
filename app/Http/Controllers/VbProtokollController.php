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
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VbProtokollController extends Controller
{
    public function __construct(
        private VbProtokollAnforderungSyncService $anforderungen,
        private SlackVorgangSync $slack,
    ) {}

    public function create(Production $production)
    {
        if ($production->vbProtokoll) {
            return redirect()->route('vb-protokoll.edit', $production->id);
        }

        $units = Unit::ordered()->get();
        $geraetetypen = Geraetetyp::orderedByUnit()->get();

        return view('vb-protokoll.create', compact('production', 'units', 'geraetetypen'));
    }

    public function store(VbProtokollRequest $request, Production $production)
    {
        if ($production->vbProtokoll) {
            return redirect()->route('vb-protokoll.edit', $production->id);
        }

        $vbProtokoll = $production->vbProtokoll()->create(array_merge(
            $request->fields(),
            ['created_by' => auth()->id()]
        ));

        $this->anforderungen->sync($vbProtokoll, $request->anforderungenInput());
        $this->storeFotos($vbProtokoll, $request);

        $this->slack->syncProduction($production);

        return redirect()
            ->route('vb-protokoll.show', $production->id)
            ->with('success', 'VB-Protokoll erfolgreich angelegt.');
    }

    public function show(Production $production)
    {
        $vbProtokoll = $production->vbProtokoll()->with(['anforderungen.unit', 'anforderungen.geraetetyp', 'fotos', 'creator'])->firstOrFail();

        return view('vb-protokoll.show', compact('production', 'vbProtokoll'));
    }

    public function edit(Production $production)
    {
        $vbProtokoll = $production->vbProtokoll()->with(['anforderungen', 'fotos'])->firstOrFail();
        $units = Unit::ordered()->get();
        $geraetetypen = Geraetetyp::orderedByUnit()->get();

        return view('vb-protokoll.edit', compact('production', 'vbProtokoll', 'units', 'geraetetypen'));
    }

    public function update(VbProtokollRequest $request, Production $production)
    {
        $vbProtokoll = $production->vbProtokoll()->firstOrFail();

        $vbProtokoll->update($request->fields());

        $this->anforderungen->sync($vbProtokoll, $request->anforderungenInput());
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
            ->with(['anforderungen.unit', 'anforderungen.geraetetyp', 'fotos', 'creator'])
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
