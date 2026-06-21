<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Department;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Users', User::count())
                ->icon('heroicon-o-users')
                ->url(route('filament.admin.resources.users.index'))
                ->color('primary'),

            Stat::make('HRD / Admin', User::where('level', 'admin')->count())
                ->icon('heroicon-o-briefcase')
                ->url(route('filament.admin.resources.users.index', [
                    'tableFilters[level][value]' => 'admin',
                ]))
                ->color('success'),

            Stat::make('IT Users', User::where('level', 'Superadmin')->count())
                ->icon('heroicon-o-cpu-chip')
                ->url(route('filament.admin.resources.users.index', [
                    'tableFilters[level][value]' => 'Superadmin',
                ]))
                ->color('warning'),

            Stat::make('Departments', Department::count())
                ->icon('heroicon-o-building-office')
                ->url(route('filament.admin.resources.departments.index'))
                ->color('gray'),
        ];
    }
}
