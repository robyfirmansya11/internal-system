<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // ✅ CEK DULU SEBELUM TAMBAH KOLOM
        if (!Schema::hasColumn('users', 'level')) {
            Schema::table('users', function (Blueprint $table) {
                $table->enum('level', [
                    'user',
                    'superuser',
                    'admin',
                    'it_superuser'
                ])->default('user')->after('password');
            });
        }
    }

    public function down(): void
    {
        // ✅ CEK DULU SEBELUM DROP
        if (Schema::hasColumn('users', 'level')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('level');
            });
        }
    }
};
