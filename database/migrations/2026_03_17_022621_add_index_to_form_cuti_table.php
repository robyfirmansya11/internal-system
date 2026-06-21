<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_cuti', function (Blueprint $table) {

            $table->index(['user_id']);
            $table->index(['department_id']);
            $table->index(['approval_level']);
            $table->index(['user_id','tanggal_mulai','tanggal_selesai']);

        });
    }

    public function down(): void
    {
        Schema::table('form_cuti', function (Blueprint $table) {

            $table->dropIndex(['user_id']);
            $table->dropIndex(['department_id']);
            $table->dropIndex(['approval_level']);
            $table->dropIndex(['user_id','tanggal_mulai','tanggal_selesai']);

        });
    }
};
