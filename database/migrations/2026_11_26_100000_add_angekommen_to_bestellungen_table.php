<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bestellungen', function (Blueprint $table) {
            $table->dateTime('angekommen_at')->nullable();
            $table->foreignId('angekommen_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('angekommen_notiz', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('bestellungen', function (Blueprint $table) {
            $table->dropConstrainedForeignId('angekommen_by');
            $table->dropColumn(['angekommen_at', 'angekommen_notiz']);
        });
    }
};
