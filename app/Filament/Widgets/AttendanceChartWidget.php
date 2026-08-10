<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class AttendanceChartWidget extends ChartWidget
{
    protected ?string $heading = 'Attendance — Last 14 Days';

    protected int|string|array $columnSpan = 12;

    protected ?string $maxHeight = '280px';

    protected ?string $pollingInterval = '60s';

    protected function getData(): array
    {
        $days = collect();

        for ($i = 13; $i >= 0; $i--) {
            $days->push(Carbon::today()->subDays($i));
        }

        $labels = $days->map(fn ($d) => $d->format('d M'))->toArray();

        $present = $days->map(fn ($d) => Attendance::whereDate('date', $d)
            ->where('status', 'present')
            ->count()
        )->toArray();

        $late = $days->map(fn ($d) => Attendance::whereDate('date', $d)
            ->where('status', 'late')
            ->count()
        )->toArray();

        $absent = $days->map(fn ($d) => Attendance::whereDate('date', $d)
            ->where('status', 'absent')
            ->count()
        )->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Present',
                    'data' => $present,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.2)',
                    'borderColor' => '#22c55e',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Late',
                    'data' => $late,
                    'backgroundColor' => 'rgba(245, 158, 11, 0.2)',
                    'borderColor' => '#f59e0b',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Absent',
                    'data' => $absent,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.2)',
                    'borderColor' => '#ef4444',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => [
                        'color' => 'rgb(156, 163, 175)',
                        'padding' => 20,
                    ],
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                        'color' => 'rgb(156, 163, 175)',
                    ],
                    'grid' => [
                        'color' => 'rgba(156, 163, 175, 0.1)',
                    ],
                ],
                'x' => [
                    'ticks' => [
                        'color' => 'rgb(156, 163, 175)',
                        'maxRotation' => 45,
                    ],
                    'grid' => [
                        'display' => false,
                    ],
                ],
            ],
        ];
    }

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user?->isSuperadmin() || $user?->isHRD();
    }
}
