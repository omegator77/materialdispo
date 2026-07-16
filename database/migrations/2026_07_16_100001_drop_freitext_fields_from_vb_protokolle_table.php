<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vb_protokolle', function (Blueprint $table) {
            $table->dropColumn([
                'besonderheiten',
                'kabelwege',
                'audio_mic',
                'audio_inear',
                'audio_kommplatz',
                'isdn_funk',
                'maz_evs_usb',
                'monitore',
                'sonstiges',
                'zeitplan',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('vb_protokolle', function (Blueprint $table) {
            $table->text('besonderheiten')->nullable();
            $table->text('kabelwege')->nullable();
            $table->text('audio_mic')->nullable();
            $table->text('audio_inear')->nullable();
            $table->text('audio_kommplatz')->nullable();
            $table->text('isdn_funk')->nullable();
            $table->text('maz_evs_usb')->nullable();
            $table->text('monitore')->nullable();
            $table->text('sonstiges')->nullable();
            $table->text('zeitplan')->nullable();
        });
    }
};
