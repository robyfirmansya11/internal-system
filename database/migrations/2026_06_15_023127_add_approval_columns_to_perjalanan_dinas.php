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
        Schema::table('perjalanan_dinas', function (Blueprint $table) {
            $table->tinyInteger('approval_level')->default(0)->after('status');
            $table->unsignedBigInteger('approved_by_manager')->nullable()->after('approved_by');
            $table->timestamp('approved_manager_at')->nullable()->after('approved_by_manager');
            $table->unsignedBigInteger('rejected_by')->nullable()->after('approved_at');
            $table->text('rejected_note')->nullable()->after('rejected_by');
        });
    }

    public function down(): void
    {
        Schema::table('perjalanan_dinas', function (Blueprint $table) {
            $table->dropColumn([
                'approval_level',
                'approved_by_manager',
                'approved_manager_at',
                'rejected_by',
                'rejected_note',
            ]);
        });
    }
};
