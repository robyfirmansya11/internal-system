<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_cuti', function (Blueprint $table) {
            if (! Schema::hasColumn('form_cuti', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('rejected_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('form_cuti', function (Blueprint $table) {
            $table->dropColumn('rejected_at');
        });
    }
};
