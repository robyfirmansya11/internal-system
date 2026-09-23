<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_cuti', function (Blueprint $table): void {
            $table->unsignedBigInteger('approved_by_finance_manager')
                ->nullable()
                ->after('approved_manager_at');
            $table->timestamp('approved_finance_manager_at')
                ->nullable()
                ->after('approved_by_finance_manager');
        });
    }

    public function down(): void
    {
        Schema::table('form_cuti', function (Blueprint $table): void {
            $table->dropColumn([
                'approved_by_finance_manager',
                'approved_finance_manager_at',
            ]);
        });
    }
};
