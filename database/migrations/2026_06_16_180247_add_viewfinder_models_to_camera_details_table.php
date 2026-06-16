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
    Schema::table('camera_details', function (Blueprint $table) {
        $table->string('large_viewfinder_model')->nullable()->after('fiber_adapter_serial');
        $table->string('small_viewfinder_model')->nullable()->after('large_viewfinder_serial');
    });
}

public function down(): void
{
    Schema::table('camera_details', function (Blueprint $table) {
        $table->dropColumn([
            'large_viewfinder_model',
            'small_viewfinder_model',
        ]);
    });
}
};
