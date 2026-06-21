<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('surat_perintah_bayars')) {

            Schema::table('surat_perintah_bayars', function (Blueprint $table) {

                if (!Schema::hasColumn('surat_perintah_bayars', 'approved_by')) {
                    $table->foreignId('approved_by')
                        ->nullable()
                        ->constrained('users')
                        ->after('status');
                }

                if (!Schema::hasColumn('surat_perintah_bayars', 'approved_at')) {
                    $table->timestamp('approved_at')
                        ->nullable()
                        ->after('approved_by');
                }

            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('surat_perintah_bayars')) {

            Schema::table('surat_perintah_bayars', function (Blueprint $table) {

                if (Schema::hasColumn('surat_perintah_bayars', 'approved_by')) {
                    $table->dropForeign(['approved_by']);
                    $table->dropColumn('approved_by');
                }

                if (Schema::hasColumn('surat_perintah_bayars', 'approved_at')) {
                    $table->dropColumn('approved_at');
                }

            });
        }
    }
};
