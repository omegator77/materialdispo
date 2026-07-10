<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_vermietvorgang', function (Blueprint $table) {
            $table->increments('id');

            $table->unsignedInteger('vermietvorgang_id');
            $table->foreign('vermietvorgang_id')->references('id')->on('vermietvorgaenge')->onDelete('cascade');

            $table->unsignedInteger('item_id');
            $table->foreign('item_id')->references('id')->on('items')->onDelete('cascade');

            $table->boolean('manual')->default(false);

            $table->timestamps();

            $table->unique(['item_id', 'vermietvorgang_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_vermietvorgang');
    }
};
