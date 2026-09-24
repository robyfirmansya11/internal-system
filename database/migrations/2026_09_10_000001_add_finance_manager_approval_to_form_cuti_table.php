<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('form_cuti', 'approved_by_finance_manager')) {
            Schema::table('form_cuti', function (Blueprint $table): void {
                $table->unsignedBigInteger('approved_by_finance_manager')
                    ->nullable()
                    ->after('approved_manager_at');
            });
        }

        if (! Schema::hasColumn('form_cuti', 'approved_finance_manager_at')) {
            Schema::table('form_cuti', function (Blueprint $table): void {
                $table->timestamp('approved_finance_manager_at')
                    ->nullable()
                    ->after('approved_by_finance_manager');
            });
        }
    }

    public function down(): void
    {
        foreach (['approved_by_finance_manager', 'approved_finance_manager_at'] as $column) {
            if (Schema::hasColumn('form_cuti', $column)) {
                Schema::table('form_cuti', function (Blueprint $table) use ($column): void {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
