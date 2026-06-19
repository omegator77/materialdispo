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
    Schema::table('item_production', function (Blueprint $table) {
        $table->text('notes')->nullable()->after('item_id');
    });
}

public function down(): void
{
    Schema::table('item_production', function (Blueprint $table) {
        $table->dropColumn('notes');
    });
}
};
