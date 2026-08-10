<?php

namespace App\Filament\Widgets;

use App\Services\ApprovalDashboardService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ApprovalSummaryWidget extends BaseWidget
{
    protected ?string $pollingInterval = '30s';

    protected int|string|array $columnSpan = 12;

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $user = auth()->user();

        return ApprovalDashboardService::pendingCounts($user)
            ->map(function ($module) {
                return Stat::make($module['label'], $module['count'])
                    ->description($module['count'] > 0 ? 'Waiting your approval' : 'All clear')
                    ->descriptionIcon($module['icon'])
                    ->color($module['count'] > 0 ? $module['color'] : 'gray')
                    ->url($module['url']);
            })
            ->toArray();
    }
}
