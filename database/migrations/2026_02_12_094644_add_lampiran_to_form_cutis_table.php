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
    // The original form_cuti table pre-dated the migration history.
    // Recreate its baseline here so new installations can migrate cleanly.
    if (! Schema::hasTable('form_cuti')) {
        Schema::create('form_cuti', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->year('tahun');
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->integer('jumlah_hari');
            $table->string('jenis_cuti');
            $table->text('alasan');
            $table->tinyInteger('approval_level')->default(0);
            $table->string('status')->default('Submitted');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejected_note')->nullable();
            $table->timestamps();
        });
    }

    // lampiran is added by the next historical migration together with
    // expired_at. Keeping it there avoids adding the same column twice.
}

public function down(): void
{
    if (Schema::hasColumn('form_cuti', 'lampiran')) {
        Schema::table('form_cuti', function (Blueprint $table) {
            $table->dropColumn('lampiran');
        });
    }
}
};
