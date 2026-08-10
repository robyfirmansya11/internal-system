<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Clock in
            $table->timestamp('clock_in')->nullable();
            $table->decimal('clock_in_lat', 10, 8)->nullable();
            $table->decimal('clock_in_lng', 11, 8)->nullable();
            $table->string('clock_in_photo')->nullable();
            $table->string('clock_in_address')->nullable();

            // Clock out
            $table->timestamp('clock_out')->nullable();
            $table->decimal('clock_out_lat', 10, 8)->nullable();
            $table->decimal('clock_out_lng', 11, 8)->nullable();
            $table->string('clock_out_photo')->nullable();
            $table->string('clock_out_address')->nullable();

            // Status
            $table->enum('status', ['present', 'late', 'absent'])->default('present');
            $table->text('note')->nullable();

            $table->date('date');
            $table->timestamps();

            // Satu karyawan hanya satu absensi per hari
            $table->unique(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
