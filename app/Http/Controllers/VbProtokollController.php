<?php

namespace App\Http\Controllers;

use App\Models\Geraetetyp;
use App\Models\Production;
use App\Models\Unit;
use App\Models\VbProtokoll;
use App\Models\VbProtokollFoto;
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

        $this->syncKameras($vbProtokoll, $validated['kameras']);
        $this->syncAnforderungen($vbProtokoll, $validated['anforderungen']);
        $this->storeFotos($vbProtokoll, $request);

        return redirect()
            ->route('vb-protokoll.show', $production->id)
            ->with('success', 'VB-Protokoll erfolgreich angelegt.');
    }

    public function show(Production $production)
    {
        $vbProtokoll = $production->vbProtokoll()->with(['kameras', 'anforderungen.unit', 'anforderungen.geraetetyp', 'fotos', 'creator'])->firstOrFail();

        return view('vb-protokoll.show', compact('production', 'vbProtokoll'));
    }

    public function edit(Production $production)
    {
        $vbProtokoll = $production->vbProtokoll()->with(['kameras', 'anforderungen', 'fotos'])->firstOrFail();
        $units = Unit::orderBy('bezeichnung')->get();
        $geraetetypen = Geraetetyp::orderBy('units_id')->orderBy('bezeichnung')->get();

        return view('vb-protokoll.edit', compact('production', 'vbProtokoll', 'units', 'geraetetypen'));
    }

    public function update(Request $request, Production $production)
    {
        $vbProtokoll = $production->vbProtokoll()->firstOrFail();

        $validated = $this->validateRequest($request);

        $vbProtokoll->update($validated['fields']);

        $this->syncKameras($vbProtokoll, $validated['kameras']);
        $this->syncAnforderungen($vbProtokoll, $validated['anforderungen']);
        $this->storeFotos($vbProtokoll, $request);

        return redirect()
            ->route('vb-protokoll.show', $production->id)
            ->with('success', 'VB-Protokoll erfolgreich aktualisiert.');
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

            'kameras' => 'nullable|array',
            'kameras.*.position' => 'nullable|integer',
            'kameras.*.bezeichnung' => 'nullable|string|max:255',

            'anforderungen' => 'nullable|array',
            'anforderungen.*.target' => 'required_with:anforderungen.*.anzahl|string',
            'anforderungen.*.anzahl' => 'required_with:anforderungen.*.target|integer|min:1',
            'anforderungen.*.notiz' => 'nullable|string|max:255',

            'fotos' => 'nullable|array',
            'fotos.*' => 'nullable|image|max:8192',
        ]);

        $fields = collect($validated)->except(['kameras', 'anforderungen', 'fotos'])->all();

        return [
            'fields' => $fields,
            'kameras' => $validated['kameras'] ?? [],
            'anforderungen' => $validated['anforderungen'] ?? [],
        ];
    }

    private function syncKameras(VbProtokoll $vbProtokoll, array $kameras): void
    {
        $vbProtokoll->kameras()->delete();

        foreach ($kameras as $kamera) {
            if (empty($kamera['bezeichnung'])) {
                continue;
            }

            $vbProtokoll->kameras()->create([
                'position' => $kamera['position'] ?? null,
                'bezeichnung' => $kamera['bezeichnung'],
            ]);
        }
    }

    private function syncAnforderungen(VbProtokoll $vbProtokoll, array $anforderungen): void
    {
        $vbProtokoll->anforderungen()->delete();

        foreach ($anforderungen as $anforderung) {
            if (empty($anforderung['target']) || empty($anforderung['anzahl'])) {
                continue;
            }

            $target = $this->splitAnforderungTarget($anforderung['target']);

            if (! $target) {
                continue;
            }

            $vbProtokoll->anforderungen()->create(array_merge($target, [
                'anzahl' => $anforderung['anzahl'],
                'notiz' => $anforderung['notiz'] ?? null,
            ]));
        }
    }

    /**
     * Parst ein Anforderungs-Zielfeld der Form "unit-5" oder "typ-12" in die
     * passende Foreign-Key-Spalte. Gibt null zurück, wenn das Ziel ungültig
     * ist oder nicht (mehr) existiert.
     */
    private function splitAnforderungTarget(string $target): ?array
    {
        [$kind, $id] = array_pad(explode('-', $target, 2), 2, null);

        if ($kind === 'unit' && Unit::where('id', $id)->exists()) {
            return ['unit_id' => $id, 'geraetetyp_id' => null];
        }

        if ($kind === 'typ' && Geraetetyp::where('id', $id)->exists()) {
            return ['unit_id' => null, 'geraetetyp_id' => $id];
        }

        return null;
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
