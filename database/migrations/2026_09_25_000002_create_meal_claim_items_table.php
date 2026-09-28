<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('meal_claim_items')) {
            return;
        }

        Schema::create('meal_claim_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('meal_claim_id')->constrained()->cascadeOnDelete();
            $table->date('meal_date');
            $table->string('meal_type', 30)->default('Meal');
            $table->string('merchant')->nullable();
            $table->decimal('amount', 15, 2);
            $table->text('note')->nullable();
            $table->string('receipt_path');
            // Fields below are intentionally prepared for the OCR/AI phase.
            $table->string('ocr_status', 30)->default('Not processed');
            $table->decimal('ocr_confidence', 5, 2)->nullable();
            $table->longText('ocr_raw_text')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meal_claim_items');
    }
};
