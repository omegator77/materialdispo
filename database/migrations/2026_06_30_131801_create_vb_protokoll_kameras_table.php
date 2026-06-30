<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vb_protokoll_kameras', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('vb_protokoll_id');
            $table->unsignedInteger('position')->nullable();
            $table->string('bezeichnung')->nullable();
            $table->timestamps();

            $table->foreign('vb_protokoll_id')->references('id')->on('vb_protokolle')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vb_protokoll_kameras');
    }
};
