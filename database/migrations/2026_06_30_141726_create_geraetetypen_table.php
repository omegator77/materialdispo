<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('geraetetypen', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('units_id');
            $table->string('bezeichnung');
            $table->string('description')->nullable();
            $table->timestamps();

            $table->foreign('units_id')->references('id')->on('units')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('geraetetypen');
    }
};
