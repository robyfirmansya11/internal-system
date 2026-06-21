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
    Schema::create('employee_profiles', function (Blueprint $table) {
        $table->id();

        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();

        // IDENTITAS
        $table->string('nik')->nullable();
        $table->string('employee_id')->nullable();
        $table->string('tempat_lahir')->nullable();
        $table->date('tanggal_lahir')->nullable();
        $table->string('jenis_kelamin')->nullable();
        $table->text('alamat')->nullable();
        $table->string('no_hp')->nullable();
        $table->string('status_pernikahan')->nullable();
        $table->string('agama')->nullable();
        $table->string('kewarganegaraan')->nullable();

        // KEPEGAWAIAN
        $table->string('status_karyawan')->nullable();
        $table->date('tanggal_masuk')->nullable();
        $table->date('tanggal_keluar')->nullable();
        $table->string('lokasi_kerja')->nullable();

        // RELASI
        $table->foreignId('atasan_id')->nullable()->constrained('users')->nullOnDelete();

        // DIGITAL
        $table->string('barcode_signature')->nullable();
        $table->string('foto')->nullable();

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_profiles');
    }
};
