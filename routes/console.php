<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Tandai karyawan yang tidak absen sebagai "Tidak Hadir",
// dijalankan tiap hari jam 15:30 WIB.
Schedule::command('attendance:mark-absent')
    ->dailyAt('15:30')
    ->timezone('Asia/Jakarta');

Schedule::command('workflow:send-reminders')
    ->dailyAt('09:00')
    ->timezone('Asia/Jakarta');
