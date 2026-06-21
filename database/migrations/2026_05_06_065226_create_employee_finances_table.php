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
    Schema::create('employee_finances', function (Blueprint $table) {
        $table->id();

        $table->foreignId('user_id')->constrained()->cascadeOnDelete();

        $table->decimal('gaji_pokok', 15, 2)->nullable();
        $table->decimal('tunjangan', 15, 2)->nullable();
        $table->string('bank_name')->nullable();
        $table->string('no_rekening')->nullable();
        $table->string('npwp')->nullable();
        $table->string('bpjs_kesehatan')->nullable();
        $table->string('bpjs_ketenagakerjaan')->nullable();

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_finances');
    }
};
