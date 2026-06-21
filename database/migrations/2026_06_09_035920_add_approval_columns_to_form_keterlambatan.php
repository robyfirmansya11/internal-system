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
        Schema::table('form_keterlambatan', function (Blueprint $table) {
            $table->unsignedBigInteger('approved_by_manager')->nullable()->after('approved_by');
            $table->timestamp('approved_manager_at')->nullable()->after('approved_by_manager');
        });
    }

    public function down(): void
    {
        Schema::table('form_keterlambatan', function (Blueprint $table) {
            $table->dropColumn(['approved_by_manager', 'approved_manager_at']);
        });
    }
};
