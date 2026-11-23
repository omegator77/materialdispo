<?php

namespace App\Services;

use App\Models\Production;

/**
 * Kamerazug-Nummerierung. (Das frühere Lernen von Zubehör aus vergangenen Zügen ist entfernt:
 * Kamerazüge entstehen aus dem VB-Protokoll-Vorschlag oder von Hand.)
 */
class CameraSetupService
{
    public function nextCamNumber(Production $production): int
    {
        $max = $production->cameraConfigs()->pluck('cam_number')
            ->map(fn ($n) => preg_match('/(\d+)\s*$/', (string) $n, $m) ? (int) $m[1] : 0)
            ->max();

        return ($max ?? 0) + 1;
    }
}
