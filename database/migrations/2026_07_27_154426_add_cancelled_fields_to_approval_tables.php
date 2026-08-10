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
        $tables = [
            'kasbons',
            'form_cuti',
            'form_keterlambatan',
            'perjalanan_dinas',
            'surat_perintah_bayars',
            'permohonan_stempels',
        ];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (! Schema::hasColumn($tableName, 'cancelled_at')) {
                    $table->timestamp('cancelled_at')
                        ->nullable()
                        ->after('rejected_note');
                }
                if (! Schema::hasColumn($tableName, 'cancelled_by')) {
                    $table->foreignId('cancelled_by')
                        ->nullable()
                        ->after('cancelled_at')
                        ->constrained('users');
                }
            });
        }
    }

    public function down(): void
    {
        $tables = [
            'kasbons',
            'form_cuti',
            'form_keterlambatan',
            'perjalanan_dinas',
            'surat_perintah_bayars',
            'permohonan_stempels',
        ];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['cancelled_by']);
                $table->dropColumn(['cancelled_at', 'cancelled_by']);
            });
        }
    }
};
