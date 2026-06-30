<?php

namespace App\Http\Controllers;

use App\Models\Geraetetyp;
use App\Models\Production;
use App\Models\Unit;
use App\Models\VbProtokoll;
use App\Models\VbProtokollFoto;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VbProtokollController extends Controller
{
    public function create(Production $production)
    {
        if ($production->vbProtokoll) {
            return redirect()->route('vb-protokoll.edit', $production->id);
        }

        $units = Unit::orderBy('bezeichnung')->get();
        $geraetetypen = Geraetetyp::orderBy('units_id')->orderBy('bezeichnung')->get();

        return view('vb-protokoll.create', compact('production', 'units', 'geraetetypen'));
    }

    public function store(Request $request, Production $production)
    {
        if ($production->vbProtokoll) {
            return redirect()->route('vb-protokoll.edit', $production->id);
        }

        $validated = $this->validateRequest($request);

        $vbProtokoll = $production->vbProtokoll()->create(array_merge(
            $validated['fields'],
            ['created_by' => auth()->id()]
        ));

        $this->syncAnforderungen($vbProtokoll, $validated['anforderungen']);
        $this->storeFotos($vbProtokoll, $request);

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
        $units = Unit::orderBy('bezeichnung')->get();
        $geraetetypen = Geraetetyp::orderBy('units_id')->orderBy('bezeichnung')->get();

        return view('vb-protokoll.edit', compact('production', 'vbProtokoll', 'units', 'geraetetypen'));
    }

    public function update(Request $request, Production $production)
    {
        $vbProtokoll = $production->vbProtokoll()->firstOrFail();

        $validated = $this->validateRequest($request);

        $vbProtokoll->update($validated['fields']);

        $this->syncAnforderungen($vbProtokoll, $validated['anforderungen']);
        $this->storeFotos($vbProtokoll, $request);

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

    private function validateRequest(Request $request): array
    {
        $validated = $request->validate([
            'kunde' => 'nullable|string|max:255',
            'produktionsort' => 'nullable|string|max:255',

            'crew_ul' => 'nullable|string|max:255',
            'crew_bt_sng' => 'nullable|string|max:255',
            'crew_ti' => 'nullable|string|max:255',
            'crew_sng' => 'nullable|string|max:255',
            'crew_bt_dl' => 'nullable|string|max:255',
            'crew_tt' => 'nullable|string|max:255',
            'crew_tl' => 'nullable|string|max:255',
            'crew_ba' => 'nullable|string|max:255',
            'crew_ta' => 'nullable|string|max:255',
            'crew_kabelhilfen' => 'nullable|string|max:255',
            'crew_kamera' => 'nullable|string|max:255',
            'crew_evs' => 'nullable|string|max:255',

            'besonderheiten' => 'nullable|string',
            'kabelwege' => 'nullable|string',
            'audio_mic' => 'nullable|string',
            'audio_inear' => 'nullable|string',
            'audio_kommplatz' => 'nullable|string',
            'isdn_funk' => 'nullable|string',
            'maz_evs_usb' => 'nullable|string',
            'monitore' => 'nullable|string',
            'sonstiges' => 'nullable|string',
            'zeitplan' => 'nullable|string',

            'anforderungen' => 'nullable|array',
            'anforderungen.*.mode' => 'nullable|string|in:typ,frei,kamera',
            'anforderungen.*.unit_id' => 'nullable|exists:units,id',
            'anforderungen.*.geraetetyp_id' => 'nullable|exists:geraetetypen,id',
            'anforderungen.*.freitext' => 'nullable|string|max:255',
            'anforderungen.*.anzahl' => 'nullable|integer|min:1',
            'anforderungen.*.notiz' => 'nullable|string|max:255',
            'anforderungen.*.cam_number' => 'nullable|string|max:255',
            'anforderungen.*.lens_geraetetyp_id' => 'nullable|exists:geraetetypen,id',
            'anforderungen.*.tripod_geraetetyp_id' => 'nullable|exists:geraetetypen,id',
            'anforderungen.*.tripod_head_geraetetyp_id' => 'nullable|exists:geraetetypen,id',
            'anforderungen.*.adapter_geraetetyp_id' => 'nullable|exists:geraetetypen,id',

            'fotos' => 'nullable|array',
            'fotos.*' => 'nullable|image|max:8192',
        ]);

        $fields = collect($validated)->except(['anforderungen', 'fotos'])->all();

        return [
            'fields' => $fields,
            'anforderungen' => $validated['anforderungen'] ?? [],
        ];
    }

    private function syncAnforderungen(VbProtokoll $vbProtokoll, array $anforderungen): void
    {
        $vbProtokoll->anforderungen()->delete();

        foreach ($anforderungen as $anforderung) {
            $mode = $anforderung['mode'] ?? 'typ';

            if ($mode === 'frei') {
                if (empty($anforderung['freitext'])) {
                    continue;
                }

                $vbProtokoll->anforderungen()->create([
                    'unit_id' => null,
                    'geraetetyp_id' => null,
                    'freitext' => $anforderung['freitext'],
                    'anzahl' => $anforderung['anzahl'] ?? null,
                    'notiz' => $anforderung['notiz'] ?? null,
                ]);

                continue;
            }

            if ($mode === 'kamera') {
                if (empty($anforderung['cam_number'])) {
                    continue;
                }

                $cameraGeraetetypId = $anforderung['geraetetyp_id'] ?? null;
                $unitId = $cameraGeraetetypId ? Geraetetyp::find($cameraGeraetetypId)?->units_id : null;

                $vbProtokoll->anforderungen()->create([
                    'unit_id' => $unitId,
                    'geraetetyp_id' => $cameraGeraetetypId,
                    'freitext' => null,
                    'anzahl' => null,
                    'cam_number' => $anforderung['cam_number'],
                    'lens_geraetetyp_id' => $anforderung['lens_geraetetyp_id'] ?? null,
                    'tripod_geraetetyp_id' => $anforderung['tripod_geraetetyp_id'] ?? null,
                    'tripod_head_geraetetyp_id' => $anforderung['tripod_head_geraetetyp_id'] ?? null,
                    'adapter_geraetetyp_id' => $anforderung['adapter_geraetetyp_id'] ?? null,
                    'notiz' => $anforderung['notiz'] ?? null,
                ]);

                continue;
            }

            if (empty($anforderung['unit_id']) && empty($anforderung['geraetetyp_id'])) {
                continue;
            }

            if (empty($anforderung['anzahl'])) {
                continue;
            }

            if (! empty($anforderung['geraetetyp_id'])) {
                $geraetetyp = Geraetetyp::find($anforderung['geraetetyp_id']);
                $unitId = $geraetetyp?->units_id;
                $geraetetypId = $anforderung['geraetetyp_id'];
            } else {
                $unitId = $anforderung['unit_id'];
                $geraetetypId = null;
            }

            $vbProtokoll->anforderungen()->create([
                'unit_id' => $unitId,
                'geraetetyp_id' => $geraetetypId,
                'freitext' => null,
                'anzahl' => $anforderung['anzahl'],
                'notiz' => $anforderung['notiz'] ?? null,
            ]);
        }
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
