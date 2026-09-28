<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('attendances') || ! Schema::hasTable('office_locations')) {
            return;
        }

        Schema::table('attendances', function (Blueprint $table): void {
            if (! Schema::hasColumn('attendances', 'clock_in_office_location_id')) {
                $table->foreignId('clock_in_office_location_id')
                    ->nullable()
                    ->constrained('office_locations')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('attendances', 'clock_out_office_location_id')) {
                $table->foreignId('clock_out_office_location_id')
                    ->nullable()
                    ->constrained('office_locations')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('attendances')) {
            return;
        }

        Schema::table('attendances', function (Blueprint $table): void {
            if (Schema::hasColumn('attendances', 'clock_in_office_location_id')) {
                $table->dropForeign(['clock_in_office_location_id']);
                $table->dropColumn('clock_in_office_location_id');
            }

            if (Schema::hasColumn('attendances', 'clock_out_office_location_id')) {
                $table->dropForeign(['clock_out_office_location_id']);
                $table->dropColumn('clock_out_office_location_id');
            }
        });
    }
};
