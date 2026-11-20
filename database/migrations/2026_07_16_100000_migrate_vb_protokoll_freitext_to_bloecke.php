<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rettet die Inhalte der alten festen Freitextfelder, BEVOR die Folge-
 * migrationen 2026_07_16_100001/100002 die Spalten droppen:
 *
 *  - vb_protokolle.<feld>            -> je nicht-leerem Feld ein Block in
 *                                       vb_protokoll_freitext_bloecke
 *                                       (Überschrift = Feldname)
 *  - vb_protokoll_anforderungen.freitext -> notiz (angehängt, falls schon belegt)
 *
 * Läuft nach der Create-Migration (..100000_create_...) und vor den Drops,
 * ist idempotent (Protokolle mit vorhandenen Blöcken werden übersprungen)
 * und ein No-op, wenn die Altspalten bereits entfernt sind.
 */
return new class extends Migration
{
    /** Altspalte => Überschrift, in der bisherigen Anzeigereihenfolge. */
    private const FIELDS = [
        'besonderheiten' => 'Besonderheiten',
        'kabelwege' => 'Kabelwege',
        'audio_mic' => 'Audio Mikrofone',
        'audio_inear' => 'Audio In-Ear',
        'audio_kommplatz' => 'Audio Kommplatz',
        'isdn_funk' => 'ISDN / Funk',
        'maz_evs_usb' => 'MAZ / EVS / USB',
        'monitore' => 'Monitore',
        'sonstiges' => 'Sonstiges',
        'zeitplan' => 'Zeitplan',
    ];

    public function up(): void
    {
        $this->backfillBloecke();
        $this->backfillAnforderungNotiz();
    }

    public function down(): void
    {
        // Bewusst leer: Die Blöcke bleiben erhalten, ein Zurückschreiben in die
        // alten Spalten würde Daten verdoppeln oder verfälschen.
    }

    private function backfillBloecke(): void
    {
        $columns = array_values(array_filter(
            array_keys(self::FIELDS),
            fn (string $col) => Schema::hasColumn('vb_protokolle', $col)
        ));

        if ($columns === [] || ! Schema::hasTable('vb_protokoll_freitext_bloecke')) {
            return;
        }

        DB::table('vb_protokolle')
            ->select(array_merge(['id'], $columns))
            ->chunkById(100, function ($protokolle) use ($columns) {
                foreach ($protokolle as $protokoll) {
                    $hatBloecke = DB::table('vb_protokoll_freitext_bloecke')
                        ->where('vb_protokoll_id', $protokoll->id)->exists();

                    if ($hatBloecke) {
                        continue;
                    }

                    $sortOrder = 0;
                    foreach ($columns as $col) {
                        $text = $protokoll->{$col};

                        if ($text === null || trim((string) $text) === '') {
                            continue;
                        }

                        DB::table('vb_protokoll_freitext_bloecke')->insert([
                            'vb_protokoll_id' => $protokoll->id,
                            'ueberschrift' => self::FIELDS[$col],
                            'text' => $text,
                            'sort_order' => $sortOrder++,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            });
    }

    private function backfillAnforderungNotiz(): void
    {
        if (! Schema::hasColumn('vb_protokoll_anforderungen', 'freitext')) {
            return;
        }

        // notiz ist string(255); angehängter Freitext kann länger sein.
        Schema::table('vb_protokoll_anforderungen', function (Blueprint $table) {
            $table->text('notiz')->nullable()->change();
        });

        DB::table('vb_protokoll_anforderungen')
            ->whereNotNull('freitext')
            ->where('freitext', '!=', '')
            ->chunkById(100, function ($rows) {
                foreach ($rows as $row) {
                    $notiz = trim((string) $row->notiz);
                    $freitext = trim((string) $row->freitext);

                    if ($freitext === '' || str_contains($notiz, $freitext)) {
                        continue;
                    }

                    DB::table('vb_protokoll_anforderungen')
                        ->where('id', $row->id)
                        ->update(['notiz' => $notiz === '' ? $freitext : $notiz.' — '.$freitext]);
                }
            });
    }
};
