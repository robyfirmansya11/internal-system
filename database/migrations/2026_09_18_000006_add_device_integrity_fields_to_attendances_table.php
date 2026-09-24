<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = [
            'device_platform' => fn (Blueprint $table) => $table->string('device_platform', 20)->nullable()->after('device_id'),
            'integrity_provider' => fn (Blueprint $table) => $table->string('integrity_provider', 30)->nullable()->after('device_platform'),
            'device_integrity_status' => fn (Blueprint $table) => $table->string('device_integrity_status', 30)->nullable()->after('integrity_provider'),
            'device_integrity_token_hash' => fn (Blueprint $table) => $table->string('device_integrity_token_hash', 64)->nullable()->after('device_integrity_status'),
            'device_integrity_checked_at' => fn (Blueprint $table) => $table->timestamp('device_integrity_checked_at')->nullable()->after('device_integrity_token_hash'),
        ];

        foreach ($columns as $column => $definition) {
            if (! Schema::hasColumn('attendances', $column)) {
                Schema::table('attendances', $definition);
            }
        }
    }

    public function down(): void
    {
        foreach ([
            'device_platform',
            'integrity_provider',
            'device_integrity_status',
            'device_integrity_token_hash',
            'device_integrity_checked_at',
        ] as $column) {
            if (Schema::hasColumn('attendances', $column)) {
                Schema::table('attendances', function (Blueprint $table) use ($column): void {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
