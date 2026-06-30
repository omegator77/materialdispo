<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->unsignedInteger('geraetetyp_id')->nullable()->after('units_id');
            $table->foreign('geraetetyp_id')->references('id')->on('geraetetypen');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropForeign(['geraetetyp_id']);
            $table->dropColumn('geraetetyp_id');
        });
    }
};
