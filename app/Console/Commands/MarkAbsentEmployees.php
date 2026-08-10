<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Console\Command;

class MarkAbsentEmployees extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'attendance:mark-absent';

    /**
     * The console command description.
     */
    protected $description = 'Tandai karyawan yang tidak melakukan clock in hari ini sebagai Tidak Hadir (absent).';

    public function handle(): int
    {
        $today = today();

        // Skip Sabtu (6) dan Minggu (0) — sesuaikan kalau perusahaan punya hari kerja beda.
        if ($today->isWeekend()) {
            $this->info('Hari ini akhir pekan, dilewati.');

            return self::SUCCESS;
        }

        // Semua user_id yang SUDAH punya record attendance hari ini (apapun statusnya)
        $existingUserIds = Attendance::where('date', $today)
            ->pluck('user_id');

        // Semua karyawan yang BELUM punya record sama sekali hari ini
        $usersWithoutAttendance = User::whereNotIn('id', $existingUserIds)
            ->get();

        $count = 0;

        foreach ($usersWithoutAttendance as $user) {
            Attendance::create([
                'user_id' => $user->id,
                'date' => $today,
                'status' => 'absent',
                'note' => 'Ditandai otomatis oleh sistem — tidak ada aktivitas clock in.',
            ]);

            $count++;
        }

        $this->info("Berhasil menandai {$count} karyawan sebagai Tidak Hadir untuk tanggal {$today->toDateString()}.");

        return self::SUCCESS;
    }
}
