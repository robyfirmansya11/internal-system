<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\UserStats;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected function getHeaderWidgets(): array
    {
        return [
            UserStats::class,
        ];
    }

    protected static bool $isLazy = false;

    public static function canView(): bool
    {
        return auth()->user()?->level === 'Admin'
            || auth()->user()?->level === 'Superadmin';
    }
}
