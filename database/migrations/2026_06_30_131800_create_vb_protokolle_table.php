<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vb_protokolle', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('production_id')->unique();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->string('kunde')->nullable();
            $table->string('produktionsort')->nullable();

            $table->string('crew_ul')->nullable();
            $table->string('crew_bt_sng')->nullable();
            $table->string('crew_ti')->nullable();
            $table->string('crew_sng')->nullable();
            $table->string('crew_bt_dl')->nullable();
            $table->string('crew_tt')->nullable();
            $table->string('crew_tl')->nullable();
            $table->string('crew_ba')->nullable();
            $table->string('crew_ta')->nullable();
            $table->string('crew_kabelhilfen')->nullable();
            $table->string('crew_kamera')->nullable();
            $table->string('crew_evs')->nullable();

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

            $table->timestamps();

            $table->foreign('production_id')->references('id')->on('productions')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vb_protokolle');
    }
};
