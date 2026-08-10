<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('lemburs', function (Blueprint $table) {
            $table->string('status')
                ->default('Submitted')
                ->change(); // pastikan 'Cancelled' bisa masuk

            $table->timestamp('cancelled_at')->nullable()->after('rejected_note');
            $table->foreignId('cancelled_by')->nullable()
                ->after('cancelled_at')
                ->constrained('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lemburs', function (Blueprint $table) {
            //
        });
    }
};
