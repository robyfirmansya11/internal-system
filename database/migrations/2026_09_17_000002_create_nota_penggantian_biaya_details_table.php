<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('nota_penggantian_biaya_details')) {
            return;
        }

        Schema::create('nota_penggantian_biaya_details', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('nota_penggantian_biaya_id')
                ->constrained('nota_penggantian_biaya')
                ->cascadeOnDelete();
            $table->text('keterangan');
            $table->decimal('jumlah', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nota_penggantian_biaya_details');
    }
};
