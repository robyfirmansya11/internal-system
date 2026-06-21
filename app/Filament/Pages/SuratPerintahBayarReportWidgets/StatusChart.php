<?php

namespace App\Filament\Pages\SuratPerintahBayarReport\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\SuratPerintahBayar;
use Illuminate\Support\Facades\DB;

class StatusChart extends ChartWidget
{
    protected ?string $heading = 'Amount by Status';

    protected function getData(): array
    {
        $data = SuratPerintahBayar::query()
            ->select(
                'status',
                DB::raw('SUM(jumlah_total) as total')
            )
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        return [
            'datasets' => [
                [
                    'data' => array_values($data),
                ],
            ],
            'labels' => array_keys($data),
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
