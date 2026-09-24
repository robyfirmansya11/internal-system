<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = [
            'clock_in_accuracy' => fn (Blueprint $table) => $table->decimal('clock_in_accuracy', 8, 2)->nullable()->after('clock_in_lng'),
            'clock_out_accuracy' => fn (Blueprint $table) => $table->decimal('clock_out_accuracy', 8, 2)->nullable()->after('clock_out_lng'),
            'device_id' => fn (Blueprint $table) => $table->string('device_id')->nullable()->after('clock_out_address'),
            'ip_address' => fn (Blueprint $table) => $table->string('ip_address', 45)->nullable()->after('device_id'),
            'user_agent' => fn (Blueprint $table) => $table->text('user_agent')->nullable()->after('ip_address'),
        ];

        foreach ($columns as $column => $definition) {
            if (! Schema::hasColumn('attendances', $column)) {
                Schema::table('attendances', $definition);
            }
        }
    }

    public function down(): void
    {
        foreach (['clock_in_accuracy', 'clock_out_accuracy', 'device_id', 'ip_address', 'user_agent'] as $column) {
            if (Schema::hasColumn('attendances', $column)) {
                Schema::table('attendances', function (Blueprint $table) use ($column): void {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
