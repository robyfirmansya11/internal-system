<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('kuota_cuti')) {
            return;
        }

        Schema::create('kuota_cuti', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->year('tahun');
            $table->unsignedInteger('kuota_tahunan')->default(0);
            $table->unsignedInteger('cuti_terpakai')->default(0);
            $table->unsignedInteger('sisa_cuti')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'tahun']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kuota_cuti');
    }
};
