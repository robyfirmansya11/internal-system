<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('form_keterlambatan', 'rejected_at')) {
            Schema::table('form_keterlambatan', function (Blueprint $table): void {
                $table->timestamp('rejected_at')->nullable()->after('rejected_by');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('form_keterlambatan', 'rejected_at')) {
            Schema::table('form_keterlambatan', function (Blueprint $table): void {
                $table->dropColumn('rejected_at');
            });
        }
    }
};
