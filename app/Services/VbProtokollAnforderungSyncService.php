<?php

namespace App\Services;

use App\Models\Geraetetyp;
use App\Models\VbProtokoll;

class VbProtokollAnforderungSyncService
{
    /**
     * Ersetzt alle Anforderungen eines VB-Protokolls durch die übergebenen
     * Zeilen. Unterstützt zwei Modi: 'typ' (Gruppe/Gerätetyp + Anzahl) und
     * 'kamera' (Kamerazug-Anforderung mit Slots für Objektiv/Stativ/Kopf/Adapter).
     */
    public function sync(VbProtokoll $vbProtokoll, array $anforderungen): void
    {
        $vbProtokoll->anforderungen()->delete();

        foreach ($anforderungen as $anforderung) {
            $mode = $anforderung['mode'] ?? 'typ';

            if ($mode === 'kamera') {
                $this->createKamera($vbProtokoll, $anforderung);

                continue;
            }

            $this->createTyp($vbProtokoll, $anforderung);
        }
    }

    private function createKamera(VbProtokoll $vbProtokoll, array $anforderung): void
    {
        if (empty($anforderung['cam_number'])) {
            return;
        }

        $cameraGeraetetypId = $anforderung['geraetetyp_id'] ?? null;
        $unitId = $cameraGeraetetypId ? Geraetetyp::find($cameraGeraetetypId)?->units_id : null;

        $vbProtokoll->anforderungen()->create([
            'unit_id' => $unitId,
            'geraetetyp_id' => $cameraGeraetetypId,
            'anzahl' => null,
            'cam_number' => $anforderung['cam_number'],
            'lens_geraetetyp_id' => $anforderung['lens_geraetetyp_id'] ?? null,
            'tripod_geraetetyp_id' => $anforderung['tripod_geraetetyp_id'] ?? null,
            'tripod_head_geraetetyp_id' => $anforderung['tripod_head_geraetetyp_id'] ?? null,
            'adapter_geraetetyp_id' => $anforderung['adapter_geraetetyp_id'] ?? null,
            'notiz' => $anforderung['notiz'] ?? null,
        ]);
    }

    private function createTyp(VbProtokoll $vbProtokoll, array $anforderung): void
    {
        if (empty($anforderung['unit_id']) && empty($anforderung['geraetetyp_id'])) {
            return;
        }

        if (empty($anforderung['anzahl'])) {
            return;
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
            'anzahl' => $anforderung['anzahl'],
            'notiz' => $anforderung['notiz'] ?? null,
        ]);
    }
}
