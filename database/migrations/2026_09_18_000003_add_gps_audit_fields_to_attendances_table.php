<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table): void {
            $table->decimal('clock_in_accuracy', 8, 2)->nullable()->after('clock_in_lng');
            $table->decimal('clock_out_accuracy', 8, 2)->nullable()->after('clock_out_lng');
            $table->string('device_id')->nullable()->after('clock_out_address');
            $table->string('ip_address', 45)->nullable()->after('device_id');
            $table->text('user_agent')->nullable()->after('ip_address');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table): void {
            $table->dropColumn(['clock_in_accuracy', 'clock_out_accuracy', 'device_id', 'ip_address', 'user_agent']);
        });
    }
};
