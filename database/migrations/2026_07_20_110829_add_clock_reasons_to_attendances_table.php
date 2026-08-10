<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            if (! Schema::hasColumn('attendances', 'clock_in_reason')) {
                $table->text('clock_in_reason')->nullable()->after('note');
            }

            if (! Schema::hasColumn('attendances', 'clock_out_reason')) {
                $table->text('clock_out_reason')->nullable()->after('clock_in_reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['clock_in_reason', 'clock_out_reason']);
        });
    }
};
