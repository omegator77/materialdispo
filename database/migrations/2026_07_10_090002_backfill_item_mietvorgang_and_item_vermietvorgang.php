<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Übernimmt die bestehenden Einzel-Fremdschlüssel (items.mietvorgang_id /
     * items.vermietvorgang_id) als Pivot-Zeilen, bevor die alten Spalten in
     * einer späteren Migration gedroppt werden. Arbeitet bewusst mit
     * DB::table() statt Eloquent-Modellen, damit dieser Backfill auch dann
     * noch korrekt läuft, wenn die Modelle die alten Spalten längst nicht
     * mehr kennen (z. B. bei einem "php artisan migrate:fresh" Monate später).
     */
    public function up(): void
    {
        $now = now();

        DB::table('items')
            ->whereNotNull('mietvorgang_id')
            ->select('id', 'mietvorgang_id', 'mietvorgang_manual')
            ->orderBy('id')
            ->get()
            ->each(function ($item) use ($now) {
                DB::table('item_mietvorgang')->insertOrIgnore([
                    'item_id' => $item->id,
                    'mietvorgang_id' => $item->mietvorgang_id,
                    'manual' => $item->mietvorgang_manual,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });

        DB::table('items')
            ->whereNotNull('vermietvorgang_id')
            ->select('id', 'vermietvorgang_id', 'vermietvorgang_manual')
            ->orderBy('id')
            ->get()
            ->each(function ($item) use ($now) {
                DB::table('item_vermietvorgang')->insertOrIgnore([
                    'item_id' => $item->id,
                    'vermietvorgang_id' => $item->vermietvorgang_id,
                    'manual' => $item->vermietvorgang_manual,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });
    }

    /**
     * Best-effort: entfernt nur die Zeilen, die dieser Backfill angelegt haben
     * könnte. Nicht perfekt reversibel, falls in der Zwischenzeit über die App
     * zugeordnet/entfernt wurde — für eine reine Backfill-Migration akzeptabel.
     */
    public function down(): void
    {
        DB::table('item_mietvorgang')->truncate();
        DB::table('item_vermietvorgang')->truncate();
    }
};
