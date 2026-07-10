<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropForeign(['mietvorgang_id']);
            $table->dropForeign(['mieter_id']);
            $table->dropForeign(['vermietvorgang_id']);
        });

        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn([
                'mietvorgang_id',
                'mietvorgang_manual',
                'rent_start',
                'rent_end',
                'mieter_id',
                'verleih_start',
                'verleih_end',
                'vermietvorgang_id',
                'vermietvorgang_manual',
            ]);
        });
    }

    /**
     * Stellt nur die Spaltenstruktur wieder her, nicht die Daten — bei einem
     * Rollback nach echter Nutzung müsste ein Rück-Backfill aus
     * item_mietvorgang/item_vermietvorgang von Hand geschrieben werden (und
     * wäre ohnehin verlustbehaftet: mehrere gleichzeitige Zuordnungen passen
     * nicht mehr in eine einzelne Fremdschlüsselspalte).
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->unsignedInteger('mietvorgang_id')->nullable()->after('suppliers_id');
            $table->boolean('mietvorgang_manual')->default(false)->after('mietvorgang_id');
            $table->date('rent_start')->nullable()->after('mietvorgang_manual');
            $table->date('rent_end')->nullable()->after('rent_start');
            $table->unsignedInteger('mieter_id')->nullable()->after('rent_end');
            $table->date('verleih_start')->nullable()->after('mieter_id');
            $table->date('verleih_end')->nullable()->after('verleih_start');
            $table->unsignedInteger('vermietvorgang_id')->nullable()->after('verleih_end');
            $table->boolean('vermietvorgang_manual')->default(false)->after('vermietvorgang_id');
        });

        Schema::table('items', function (Blueprint $table) {
            $table->foreign('mietvorgang_id')->references('id')->on('mietvorgaenge')->nullOnDelete();
            $table->foreign('mieter_id')->references('id')->on('mieter')->onDelete('cascade');
            $table->foreign('vermietvorgang_id')->references('id')->on('vermietvorgaenge')->nullOnDelete();
        });
    }
};
