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
        // Fix status dari enum/varchar lama ke nilai baru
        DB::statement("
        ALTER TABLE permohonan_stempels
        MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'Submitted'
    ");

        DB::statement("
        UPDATE permohonan_stempels SET status = CASE status
            WHEN 'pending'  THEN 'Pending Approval'
            WHEN 'approved' THEN 'Approved'
            WHEN 'rejected' THEN 'Rejected'
            ELSE 'Submitted'
        END
    ");

        Schema::table('permohonan_stempels', function (Blueprint $table) {
            $table->tinyInteger('approval_level')->default(0)->after('status');
            $table->unsignedBigInteger('approved_by_manager')->nullable()->after('approved_by');
            $table->timestamp('approved_manager_at')->nullable()->after('approved_by_manager');
            $table->unsignedBigInteger('rejected_by')->nullable()->after('approved_at');
            $table->text('rejected_note')->nullable()->after('rejected_by');
        });
    }

    public function down(): void
    {
        Schema::table('permohonan_stempels', function (Blueprint $table) {
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
