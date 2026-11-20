<?php

use App\Models\Production;
use App\Models\Unit;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Die Migrationen 2026_07_16_100001/100002 droppen die alten Freitext-Spalten.
 * Der Backfill (2026_07_16_100000_migrate_...) muss die Inhalte vorher in
 * vb_protokoll_freitext_bloecke bzw. notiz retten. Die Tests bauen den
 * Altzustand (Spalten) nach dem Voll-Migrate wieder auf und führen den
 * Backfill direkt aus.
 */
function backfillMigration(): object
{
    return require database_path('migrations/2026_07_16_100000_migrate_vb_protokoll_freitext_to_bloecke.php');
}

function restoreLegacyFreitextColumns(): void
{
    Schema::table('vb_protokolle', function (Blueprint $table) {
        foreach (['besonderheiten', 'kabelwege', 'audio_mic', 'audio_inear', 'audio_kommplatz', 'isdn_funk', 'maz_evs_usb', 'monitore', 'sonstiges', 'zeitplan'] as $col) {
            $table->text($col)->nullable();
        }
    });
    Schema::table('vb_protokoll_anforderungen', function (Blueprint $table) {
        $table->string('freitext')->nullable();
    });
}

function makeLegacyProtokoll(array $legacy = []): int
{
    $production = Production::create([
        'bezeichnung' => 'P '.uniqid(),
        'booking_start' => '2026-05-01',
        'booking_end' => '2026-05-02',
    ]);

    return DB::table('vb_protokolle')->insertGetId(array_merge([
        'production_id' => $production->id,
        'created_at' => now(),
        'updated_at' => now(),
    ], $legacy));
}

test('Backfill überführt nicht-leere Freitextfelder als Blöcke in Spaltenreihenfolge', function () {
    restoreLegacyFreitextColumns();
    $id = makeLegacyProtokoll([
        'besonderheiten' => 'Nur Haupteingang',
        'kabelwege' => '   ',
        'zeitplan' => '08:00 Aufbau',
    ]);

    backfillMigration()->up();

    $bloecke = DB::table('vb_protokoll_freitext_bloecke')
        ->where('vb_protokoll_id', $id)->orderBy('sort_order')->get();

    expect($bloecke)->toHaveCount(2)
        ->and($bloecke[0]->ueberschrift)->toBe('Besonderheiten')
        ->and($bloecke[0]->text)->toBe('Nur Haupteingang')
        ->and($bloecke[0]->sort_order)->toBe(0)
        ->and($bloecke[1]->ueberschrift)->toBe('Zeitplan')
        ->and($bloecke[1]->text)->toBe('08:00 Aufbau')
        ->and($bloecke[1]->sort_order)->toBe(1);
});

test('Backfill ist idempotent und überschreibt vorhandene Blöcke nicht', function () {
    restoreLegacyFreitextColumns();
    $id = makeLegacyProtokoll(['besonderheiten' => 'Alt']);

    backfillMigration()->up();
    backfillMigration()->up();

    expect(DB::table('vb_protokoll_freitext_bloecke')->where('vb_protokoll_id', $id)->count())->toBe(1);

    $neu = makeLegacyProtokoll(['besonderheiten' => 'Alt']);
    DB::table('vb_protokoll_freitext_bloecke')->insert([
        'vb_protokoll_id' => $neu, 'ueberschrift' => 'Neu', 'text' => 'Bereits im neuen System',
        'sort_order' => 0, 'created_at' => now(), 'updated_at' => now(),
    ]);

    backfillMigration()->up();

    expect(DB::table('vb_protokoll_freitext_bloecke')->where('vb_protokoll_id', $neu)->pluck('text')->all())
        ->toBe(['Bereits im neuen System']);
});

test('Backfill überführt anforderungen.freitext in notiz ohne vorhandene Notiz zu verlieren', function () {
    restoreLegacyFreitextColumns();
    $unit = Unit::create(['bezeichnung' => 'Kameras']);
    $id = makeLegacyProtokoll();

    $row = fn (array $x) => DB::table('vb_protokoll_anforderungen')->insertGetId(array_merge([
        'vb_protokoll_id' => $id, 'unit_id' => $unit->id, 'anzahl' => 1,
        'created_at' => now(), 'updated_at' => now(),
    ], $x));

    $nurFreitext = $row(['freitext' => 'Bitte schwarz']);
    $beides = $row(['notiz' => 'Notiz A', 'freitext' => 'Freitext B']);
    $nurNotiz = $row(['notiz' => 'Nur Notiz']);

    backfillMigration()->up();

    $notiz = fn ($rid) => DB::table('vb_protokoll_anforderungen')->where('id', $rid)->value('notiz');

    expect($notiz($nurFreitext))->toBe('Bitte schwarz')
        ->and($notiz($beides))->toContain('Notiz A')->toContain('Freitext B')
        ->and($notiz($nurNotiz))->toBe('Nur Notiz');
});

test('Backfill ist ein No-op, wenn die Altspalten schon entfernt sind', function () {
    $id = makeLegacyProtokoll();

    backfillMigration()->up();

    expect(DB::table('vb_protokoll_freitext_bloecke')->where('vb_protokoll_id', $id)->count())->toBe(0);
});

test('Backfill läuft vor den Drop-Migrationen', function () {
    $files = collect(glob(database_path('migrations/2026_07_16_*.php')))
        ->map(fn ($f) => basename($f, '.php'))->sort()->values();

    $backfill = $files->search(fn ($f) => str_contains($f, 'migrate_vb_protokoll_freitext_to_bloecke'));
    $drop = $files->search(fn ($f) => str_contains($f, 'drop_freitext_fields_from_vb_protokolle'));

    expect($backfill)->not->toBeFalse()
        ->and($backfill)->toBeLessThan($drop);
});
