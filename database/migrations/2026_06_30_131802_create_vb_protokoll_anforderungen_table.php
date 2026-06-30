<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vb_protokoll_anforderungen', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('vb_protokoll_id');
            $table->unsignedInteger('unit_id');
            $table->unsignedInteger('anzahl');
            $table->string('notiz')->nullable();
            $table->timestamps();

            $table->foreign('vb_protokoll_id')->references('id')->on('vb_protokolle')->onDelete('cascade');
            $table->foreign('unit_id')->references('id')->on('units')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vb_protokoll_anforderungen');
    }
};
