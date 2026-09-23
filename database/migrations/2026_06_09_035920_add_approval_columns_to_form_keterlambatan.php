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
        // The base table existed before the project kept complete migrations.
        // Creating it conditionally makes fresh installs and tests reproducible.
        if (! Schema::hasTable('form_keterlambatan')) {
            Schema::create('form_keterlambatan', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('department_id')->constrained()->cascadeOnDelete();
                $table->date('tanggal');
                $table->time('jam_masuk');
                $table->text('alasan');
                $table->tinyInteger('approval_level')->default(0);
                $table->string('status')->default('Submitted');
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('rejected_note')->nullable();
                $table->timestamps();
            });
        }

        Schema::table('form_keterlambatan', function (Blueprint $table) {
            $table->unsignedBigInteger('approved_by_manager')->nullable()->after('approved_by');
            $table->timestamp('approved_manager_at')->nullable()->after('approved_by_manager');
        });
    }

    public function down(): void
    {
        Schema::table('form_keterlambatan', function (Blueprint $table) {
            $table->dropColumn(['approved_by_manager', 'approved_manager_at']);
        });
    }
};
