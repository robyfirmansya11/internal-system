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
        Schema::table('surat_perintah_bayars', function (Blueprint $table) {
            if (! Schema::hasColumn('surat_perintah_bayars', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('cancelled_by');
            }
            if (! Schema::hasColumn('surat_perintah_bayars', 'paid_by')) {
                $table->foreignId('paid_by')
                    ->nullable()
                    ->after('paid_at')
                    ->constrained('users');
            }
        });
    }

    public function down(): void
    {
        Schema::table('surat_perintah_bayars', function (Blueprint $table) {
            $table->dropForeign(['paid_by']);
            $table->dropColumn(['paid_at', 'paid_by']);
        });
    }
};
