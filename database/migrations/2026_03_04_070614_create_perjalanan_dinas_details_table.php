<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perjalanan_dinas_details', function (Blueprint $table) {
            $table->id();

            $table->foreignId('perjalanan_dinas_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->date('tanggal_berangkat');
            $table->time('waktu_berangkat')->nullable();
            $table->string('tempat_berangkat');

            $table->date('tanggal_tujuan');
            $table->time('waktu_tujuan')->nullable();
            $table->string('tempat_tujuan');

            $table->bigInteger('jumlah_hari')->default(1);

            $table->double('amount_transportasi')->default(0);
            $table->double('amount_tunjangan')->default(0);
            $table->bigInteger('lama_hotel')->default(0);
            $table->double('amount_hotel')->default(0);
            $table->double('misc')->default(0);
            $table->double('amount_other')->default(0);

            $table->double('subtotal')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perjalanan_dinas_details');
    }
};
