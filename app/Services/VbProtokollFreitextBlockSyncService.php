<?php

namespace App\Services;

use App\Models\VbProtokoll;
use Illuminate\Support\Facades\DB;

class VbProtokollFreitextBlockSyncService
{
    /**
     * Ersetzt alle Freitext-Blöcke eines VB-Protokolls durch die übergebenen
     * Zeilen (Überschrift + Text, frei benennbar). Leere Blöcke werden verworfen.
     */
    public function sync(VbProtokoll $vbProtokoll, array $bloecke): void
    {
        // Atomar: scheitert ein Block, bleiben die bisherigen Blöcke erhalten.
        DB::transaction(function () use ($vbProtokoll, $bloecke) {
            $vbProtokoll->freitextBloecke()->delete();

            $sortOrder = 0;

            foreach ($bloecke as $block) {
                if (empty($block['ueberschrift']) && empty($block['text'])) {
                    continue;
                }

                $vbProtokoll->freitextBloecke()->create([
                    'ueberschrift' => $block['ueberschrift'] ?? null,
                    'text' => $block['text'] ?? null,
                    'sort_order' => $sortOrder++,
                ]);
            }
        });
    }
}
