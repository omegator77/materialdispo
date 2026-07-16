<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vb_protokoll_freitext_bloecke', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('vb_protokoll_id');
            $table->string('ueberschrift')->nullable();
            $table->text('text')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('vb_protokoll_id')->references('id')->on('vb_protokolle')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vb_protokoll_freitext_bloecke');
    }
};
