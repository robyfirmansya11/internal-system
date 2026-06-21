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
        DB::statement("
        ALTER TABLE kasbons MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'Submitted'
    ");

        DB::statement("
        UPDATE kasbons SET status = CASE status
            WHEN 'pending'  THEN 'Pending Approval'
            WHEN 'approved' THEN 'Approved'
            WHEN 'rejected' THEN 'Rejected'
            ELSE 'Submitted'
        END
    ");

        Schema::table('kasbons', function (Blueprint $table) {
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->tinyInteger('approval_level')->default(0)->after('status');
            $table->unsignedBigInteger('approved_by_manager')->nullable()->after('approved_by');
            $table->timestamp('approved_manager_at')->nullable()->after('approved_by_manager');
            $table->unsignedBigInteger('rejected_by')->nullable()->after('approved_at');
            $table->text('rejected_note')->nullable()->after('rejected_by');
        });
    }

    public function down(): void
    {
        Schema::table('kasbons', function (Blueprint $table) {
            $table->dropColumn([
                'approved_at',
                'approval_level',
                'approved_by_manager',
                'approved_manager_at',
                'rejected_by',
                'rejected_note',
            ]);
        });
    }
};
