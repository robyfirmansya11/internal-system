<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Langkah 1: Ubah kolom DULU dari ENUM ke varchar
        // Harus pakai raw SQL karena Doctrine tidak handle ENUM dengan baik
        DB::statement("ALTER TABLE perjalanan_dinas MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'Submitted'");

        // Langkah 2: Baru update data lama ke nilai baru
        DB::statement("
            UPDATE perjalanan_dinas SET status = CASE status
                WHEN 'draft'      THEN 'Submitted'
                WHEN 'submitted'  THEN 'Pending Approval'
                WHEN 'approved'   THEN 'Approved'
                WHEN 'rejected'   THEN 'Rejected'
                ELSE 'Submitted'
            END
        ");
    }

    public function down(): void
    {
        // Kembalikan data dulu
        DB::statement("
            UPDATE perjalanan_dinas SET status = CASE status
                WHEN 'Submitted'        THEN 'draft'
                WHEN 'Pending Approval' THEN 'submitted'
                WHEN 'Approved'         THEN 'approved'
                WHEN 'Rejected'         THEN 'rejected'
                ELSE 'draft'
            END
        ");

        // Kembalikan ke ENUM
        DB::statement("ALTER TABLE perjalanan_dinas MODIFY COLUMN status ENUM('draft','submitted','approved','rejected') NOT NULL DEFAULT 'draft'");
    }
};
