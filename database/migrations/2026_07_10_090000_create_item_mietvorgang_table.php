<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_mietvorgang', function (Blueprint $table) {
            $table->increments('id');

            $table->unsignedInteger('mietvorgang_id');
            $table->foreign('mietvorgang_id')->references('id')->on('mietvorgaenge')->onDelete('cascade');

            $table->unsignedInteger('item_id');
            $table->foreign('item_id')->references('id')->on('items')->onDelete('cascade');

            $table->boolean('manual')->default(false);

            $table->timestamps();

            $table->unique(['item_id', 'mietvorgang_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_mietvorgang');
    }
};
