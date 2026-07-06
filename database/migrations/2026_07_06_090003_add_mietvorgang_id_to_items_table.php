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
        Schema::table('items', function (Blueprint $table) {
            $table->unsignedInteger('mietvorgang_id')->nullable()->after('suppliers_id');
            $table->boolean('mietvorgang_manual')->default(false)->after('mietvorgang_id');
        });

        Schema::table('items', function (Blueprint $table) {
            $table->foreign('mietvorgang_id')
                ->references('id')
                ->on('mietvorgaenge')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropForeign(['mietvorgang_id']);
            $table->dropColumn(['mietvorgang_id', 'mietvorgang_manual']);
        });
    }
};
