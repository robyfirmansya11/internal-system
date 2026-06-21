<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surat_perintah_bayars', function (Blueprint $table) {

            $table->id();

            // Foreign Keys
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();

            // Informasi Invoice
            $table->date('tanggal_penagihan');
            $table->date('tanggal_jatuhtempo');

            $table->string('no_invoice');

            // Nominal
            $table->double('jumlah', 15, 2)->default(0);
            $table->double('ppn', 15, 2)->default(0);
            $table->double('pph', 15, 2)->default(0);
            $table->double('admin', 15, 2)->default(0);

            $table->double('jumlah_total', 15, 2)->default(0);

            // Informasi tambahan
            $table->string('terbilang')->nullable();
            $table->string('pembayaran_tahap')->nullable();
            $table->string('jumlah_lampiran')->nullable();

            $table->text('keterangan')->nullable();
            $table->text('informasi_transfer')->nullable();

            $table->string('lampiran')->nullable();

            // Status approval
            $table->enum('status', [
                'pending',
                'approved',
                'rejected'
            ])->default('pending');

            $table->timestamp('approved_at')->nullable();

            // Laravel standard
            $table->timestamps();
            $table->softDeletes();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surat_perintah_bayars');
    }
};
