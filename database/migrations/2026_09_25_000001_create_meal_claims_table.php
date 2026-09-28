<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('meal_claims')) {
            return;
        }

        Schema::create('meal_claims', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('department_id')->constrained('departments');
            $table->date('claim_date');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->unsignedSmallInteger('receipt_count')->default(0);
            $table->string('status', 50)->default('Submitted')->index();
            $table->tinyInteger('approval_level')->default(0);
            $table->text('employee_note')->nullable();
            $table->foreignId('receipt_received_by')->nullable()->constrained('users');
            $table->timestamp('receipt_received_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users');
            $table->timestamp('verified_at')->nullable();
            $table->text('verification_note')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users');
            $table->timestamp('paid_at')->nullable();
            $table->text('payment_note')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users');
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejected_note')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users');
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meal_claims');
    }
};
