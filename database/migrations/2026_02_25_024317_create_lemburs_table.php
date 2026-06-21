<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lemburs', function (Blueprint $table) {

            $table->id();

            // Relasi
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();

            // Informasi lembur
            $table->string('bulan_lembur');
            $table->date('tanggal_lembur');

            $table->time('mulai_kerja');
            $table->time('selesai_kerja');

            $table->time('mulai_lembur');
            $table->time('selesai_lembur');

            $table->decimal('uang_makan', 12, 2)->nullable();

            $table->text('uraian_pekerjaan');

            $table->decimal('jumlah_jam_lembur', 5, 2);

            // Approval
            $table->string('status')->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lemburs');
    }
};