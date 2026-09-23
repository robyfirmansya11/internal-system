<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nota_penggantian_biaya', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('department_id')->constrained('departments');

            $table->date('tanggal');
            $table->text('keterangan');
            $table->decimal('jumlah', 15, 2);
            $table->decimal('jumlah_total', 15, 2);
            $table->string('terbilang')->nullable();
            $table->text('informasi_transfer')->nullable();
            $table->unsignedInteger('jumlah_lampiran')->default(0);
            $table->string('lampiran')->nullable();

            $table->string('status', 50)->default('Submitted');
            $table->tinyInteger('approval_level')->default(0);
            $table->foreignId('approved_by_manager')->nullable()->constrained('users');
            $table->timestamp('approved_manager_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users');
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejected_note')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users');
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nota_penggantian_biaya');
    }
};
