<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('mietvorgaenge', function (Blueprint $table) {
            $table->string('bezeichnung')->nullable()->after('id');
        });

        Schema::table('vermietvorgaenge', function (Blueprint $table) {
            $table->string('bezeichnung')->nullable()->after('id');
        });

        $this->backfill('mietvorgaenge', 'suppliers_id', 'suppliers', 'M');
        $this->backfill('vermietvorgaenge', 'mieter_id', 'mieter', 'V');
    }

    /**
     * Vergibt bestehenden Zeilen eine Bezeichnung "{Partei} {Typ}-JJMMNN",
     * NN fortlaufend pro Kalendermonat der Anlage (created_at) — unabhängig
     * von der Partei, damit der Code allein schon eindeutig ist.
     */
    private function backfill(string $table, string $foreignKey, string $partyTable, string $typeLetter): void
    {
        $rows = DB::table($table)->orderBy('created_at')->get(['id', $foreignKey, 'created_at']);
        $counters = [];

        foreach ($rows as $row) {
            $scope = \Carbon\Carbon::parse($row->created_at)->format('ym');
            $counters[$scope] = ($counters[$scope] ?? 0) + 1;

            $partyName = DB::table($partyTable)->where('id', $row->{$foreignKey})->value('bezeichnung') ?? 'Unbekannt';
            $code = "{$typeLetter}-{$scope}".sprintf('%02d', $counters[$scope]);

            DB::table($table)->where('id', $row->id)->update(['bezeichnung' => "{$partyName} {$code}"]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mietvorgaenge', function (Blueprint $table) {
            $table->dropColumn('bezeichnung');
        });

        Schema::table('vermietvorgaenge', function (Blueprint $table) {
            $table->dropColumn('bezeichnung');
        });
    }
};
