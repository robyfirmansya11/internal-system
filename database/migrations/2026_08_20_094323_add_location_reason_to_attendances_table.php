<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            if (! Schema::hasColumn('attendances', 'clock_in_location_reason')) {
                $table->text('clock_in_location_reason')->nullable()->after('clock_out_reason');
            }
            if (! Schema::hasColumn('attendances', 'clock_out_location_reason')) {
                $table->text('clock_out_location_reason')->nullable()->after('clock_in_location_reason');
            }
            if (! Schema::hasColumn('attendances', 'is_outside_radius')) {
                $table->boolean('is_outside_radius')->default(false)->after('clock_out_location_reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn([
                'clock_in_location_reason',
                'clock_out_location_reason',
                'is_outside_radius',
            ]);
        });
    }
};
