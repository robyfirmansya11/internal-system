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
    if (Schema::hasTable('form_cuti')) {
        if (!Schema::hasColumn('form_cuti', 'lampiran')) {
            Schema::table('form_cuti', function (Blueprint $table) {
                $table->string('lampiran')->nullable()->after('alasan');
            });
        }
    }
}

public function down(): void
{
    if (Schema::hasColumn('form_cuti', 'lampiran')) {
        Schema::table('form_cuti', function (Blueprint $table) {
            $table->dropColumn('lampiran');
        });
    }
}
};
