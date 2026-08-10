<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AttendanceTodayWidget extends BaseWidget
{
    protected ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = 12;

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $user = auth()->user();

        if (! ($user->isSuperadmin() || $user->isHRD())) {
            return [];
        }

        $totalEmployees = User::count();

        $presentToday = Attendance::whereDate('date', today())
            ->where('status', 'present')
            ->count();

        $lateToday = Attendance::whereDate('date', today())
            ->where('status', 'late')
            ->count();

        // Employees who have actually checked in today
        $checkedInToday = Attendance::whereDate('date', today())
            ->whereNotNull('clock_in')
            ->count();

        $pendingCheckIn = $totalEmployees - $checkedInToday;

        return [
            Stat::make('Present Today', $presentToday)
                ->description('On time')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success')
                ->chart([
                    $presentToday,
                    max(0, $presentToday - 1),
                    max(0, $presentToday - 2),
                ]),

            Stat::make('Late Arrivals', $lateToday)
                ->description('After 08:30 AM')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color('warning'),

            Stat::make('Pending Check-In', $pendingCheckIn)
                ->description("Out of {$totalEmployees} employees")
                ->descriptionIcon('heroicon-o-user-minus')
                ->color($pendingCheckIn > 0 ? 'danger' : 'success'),

            Stat::make('Checked In Today', $checkedInToday)
                ->description(today()->format('l, d F Y'))
                ->descriptionIcon('heroicon-o-calendar-days')
                ->color('info'),
        ];
    }

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user?->isSuperadmin() || $user?->isHRD();
    }
}
