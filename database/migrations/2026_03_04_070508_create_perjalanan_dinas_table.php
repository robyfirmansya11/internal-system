<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perjalanan_dinas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            $table->text('keterangan')->nullable();
            $table->bigInteger('jumlah_lampiran')->default(0);

            $table->double('total')->default(0);
            $table->string('terbilang')->nullable();
            $table->text('catatan')->nullable();

            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();

            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])
                  ->default('draft');

            $table->text('note_rejected')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perjalanan_dinas');
    }
};
