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
    Schema::table('form_cuti', function (Blueprint $table) {

        // APPROVAL LEVEL 1 (SUPERUSER)
        $table->unsignedBigInteger('approved_by_manager')->nullable()->after('approved_by');
        $table->timestamp('approved_manager_at')->nullable()->after('approved_by_manager');

        // APPROVAL LEVEL 2 (HRD / ADMIN)
        $table->unsignedBigInteger('approved_by_hrd')->nullable()->after('approved_manager_at');
        $table->timestamp('approved_hrd_at')->nullable()->after('approved_by_hrd');

    });
}

public function down(): void
{
    Schema::table('form_cuti', function (Blueprint $table) {
        $table->dropColumn([
            'approved_by_manager',
            'approved_manager_at',
            'approved_by_hrd',
            'approved_hrd_at'
        ]);
    });
}
};
