<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productions', function (Blueprint $table) {
            $table->timestamp('packvorgang_confirmed_at')->nullable()->after('packlist_notes');
            $table->foreignId('packvorgang_confirmed_by')->nullable()->after('packvorgang_confirmed_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('productions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('packvorgang_confirmed_by');
            $table->dropColumn('packvorgang_confirmed_at');
        });
    }
};
