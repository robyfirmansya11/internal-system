<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permohonan_stempels', function (Blueprint $table) {

            $table->id();

            // relasi utama
            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('department_id')
                ->constrained('departments')
                ->cascadeOnDelete();

            // data permohonan
            $table->date('tanggal');

            $table->string('tujuan');

            $table->text('keterangan')
                ->nullable();

            $table->date('tanggal_surat')
                ->nullable();

            $table->date('tanggal_stempel')
                ->nullable();

            $table->string('nomor_surat')
                ->nullable();

            $table->string('ditandatangani_oleh')
                ->nullable();

            $table->string('lampiran')
                ->nullable();

            // approval
            $table->enum('status', [
                'pending',
                'approved',
                'rejected'
            ])->default('pending');

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('approved_at')
                ->nullable();

            $table->timestamps();

            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permohonan_stempels');
    }
};
