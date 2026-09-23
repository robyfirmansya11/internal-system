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
                $table->string('level')->default('User')->after('password');
            });
        }

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'jabatan')) {
                $table->string('jabatan')->nullable()->after('level');
            }
            if (! Schema::hasColumn('users', 'tanggal_masuk')) {
                $table->date('tanggal_masuk')->nullable();
            }
            if (! Schema::hasColumn('users', 'foto')) {
                $table->string('foto')->nullable();
            }
        });
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
