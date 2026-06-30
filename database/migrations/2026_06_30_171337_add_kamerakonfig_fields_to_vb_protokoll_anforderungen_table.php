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
            $table->string('cam_number')->nullable()->after('freitext');
            $table->unsignedInteger('lens_geraetetyp_id')->nullable()->after('cam_number');
            $table->unsignedInteger('tripod_geraetetyp_id')->nullable()->after('lens_geraetetyp_id');
            $table->unsignedInteger('tripod_head_geraetetyp_id')->nullable()->after('tripod_geraetetyp_id');
            $table->unsignedInteger('adapter_geraetetyp_id')->nullable()->after('tripod_head_geraetetyp_id');

            $table->foreign('lens_geraetetyp_id')->references('id')->on('geraetetypen');
            $table->foreign('tripod_geraetetyp_id')->references('id')->on('geraetetypen');
            $table->foreign('tripod_head_geraetetyp_id')->references('id')->on('geraetetypen');
            $table->foreign('adapter_geraetetyp_id')->references('id')->on('geraetetypen');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vb_protokoll_anforderungen', function (Blueprint $table) {
            $table->dropForeign(['lens_geraetetyp_id']);
            $table->dropForeign(['tripod_geraetetyp_id']);
            $table->dropForeign(['tripod_head_geraetetyp_id']);
            $table->dropForeign(['adapter_geraetetyp_id']);

            $table->dropColumn([
                'cam_number',
                'lens_geraetetyp_id',
                'tripod_geraetetyp_id',
                'tripod_head_geraetetyp_id',
                'adapter_geraetetyp_id',
            ]);
        });
    }
};
