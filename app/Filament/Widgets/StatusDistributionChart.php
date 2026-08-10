<?php

namespace App\Filament\Widgets;

use App\Services\ApprovalDashboardService;
use Filament\Widgets\ChartWidget;

class StatusDistributionChart extends ChartWidget
{
    protected ?string $heading = 'Status Distribution — This Month';

    protected int|string|array $columnSpan = 12;

    protected ?string $maxHeight = '260px';

    protected function getData(): array
    {
        $counts = ApprovalDashboardService::statusDistributionThisMonth();

        return [
            'datasets' => [
                [
                    'data' => array_values($counts),
                    'backgroundColor' => [
                        '#f59e0b', // Pending — warning
                        '#22c55e', // Approved — success
                        '#ef4444', // Rejected — danger
                    ],
                    'borderWidth' => 0,
                ],
            ],
            'labels' => array_keys($counts),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => [
                        'color' => 'rgb(156, 163, 175)',
                    ],
                ],
            ],
        ];
    }
}
