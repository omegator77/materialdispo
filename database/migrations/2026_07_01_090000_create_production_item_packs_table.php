<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_item_packs', function (Blueprint $table) {
            $table->increments('id');

            $table->unsignedInteger('production_id');
            $table->foreign('production_id')->references('id')->on('productions')->onDelete('cascade');

            $table->unsignedInteger('item_id');
            $table->foreign('item_id')->references('id')->on('items')->onDelete('cascade');

            $table->foreignId('packed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('packed_at');
            $table->timestamps();

            $table->unique(['production_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_item_packs');
    }
};
