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
            $table->unsignedInteger('mieter_id')->nullable()->after('mietvorgang_manual');
            $table->date('verleih_start')->nullable()->after('mieter_id');
            $table->date('verleih_end')->nullable()->after('verleih_start');
            $table->unsignedInteger('vermietvorgang_id')->nullable()->after('verleih_end');
            $table->boolean('vermietvorgang_manual')->default(false)->after('vermietvorgang_id');
        });

        Schema::table('items', function (Blueprint $table) {
            $table->foreign('mieter_id')
                ->references('id')
                ->on('mieter')
                ->onDelete('cascade');

            $table->foreign('vermietvorgang_id')
                ->references('id')
                ->on('vermietvorgaenge')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropForeign(['mieter_id']);
            $table->dropForeign(['vermietvorgang_id']);
            $table->dropColumn(['mieter_id', 'verleih_start', 'verleih_end', 'vermietvorgang_id', 'vermietvorgang_manual']);
        });
    }
};
