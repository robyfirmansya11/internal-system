<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use App\Models\User;
use Filament\Widgets\Widget;

class AbsentTodayWidget extends Widget
{
    protected string $view = 'filament.widgets.absent-today-widget';

    protected ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = 12;

    public function getAbsentUsers()
    {
        // Ambil SEMUA user, tidak dibatasi level tertentu
        $allUsers = User::with('departments')->get();

        // User yang SUDAH clock-in hari ini (apapun statusnya: present/late)
        $clockedInUserIds = Attendance::whereDate('date', today())
            ->whereNotNull('clock_in')
            ->pluck('user_id');

        // Return user yang belum clock-in
        return $allUsers->whereNotIn('id', $clockedInUserIds)->values();
    }

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user?->isSuperadmin() || $user?->isHRD();
    }
}
