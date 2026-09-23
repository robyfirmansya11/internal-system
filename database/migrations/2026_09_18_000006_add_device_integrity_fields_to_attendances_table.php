<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table): void {
            $table->string('device_platform', 20)->nullable()->after('device_id');
            $table->string('integrity_provider', 30)->nullable()->after('device_platform');
            $table->string('device_integrity_status', 30)->nullable()->after('integrity_provider');
            $table->string('device_integrity_token_hash', 64)->nullable()->after('device_integrity_status');
            $table->timestamp('device_integrity_checked_at')->nullable()->after('device_integrity_token_hash');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table): void {
            $table->dropColumn([
                'device_platform',
                'integrity_provider',
                'device_integrity_status',
                'device_integrity_token_hash',
                'device_integrity_checked_at',
            ]);
        });
    }
};
