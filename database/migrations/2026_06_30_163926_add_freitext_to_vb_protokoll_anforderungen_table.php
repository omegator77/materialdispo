<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('vb_protokoll_anforderungen', function (Blueprint $table) {
            $table->string('freitext')->nullable()->after('geraetetyp_id');
            $table->unsignedInteger('anzahl')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vb_protokoll_anforderungen', function (Blueprint $table) {
            $table->dropColumn('freitext');
            $table->unsignedInteger('anzahl')->nullable(false)->change();
        });
    }
};
