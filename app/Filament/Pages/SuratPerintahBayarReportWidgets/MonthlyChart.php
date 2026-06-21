<?php

namespace App\Filament\Pages\SuratPerintahBayarReport\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\SuratPerintahBayar;
use Illuminate\Support\Facades\DB;

use Filament\Widgets\Concerns\InteractsWithPageFilters;

class MonthlyChart extends ChartWidget
{

use InteractsWithPageFilters;
    protected ?string $heading = 'Monthly Amount';


 protected function getData(): array
{
    $query = SuratPerintahBayar::query()

        ->when(
            $this->filters['year'] ?? null,
            fn ($q, $year) => $q->whereYear('tanggal_penagihan', $year)
        )
        ->when(
            $this->filters['company_id'] ?? null,
            fn ($q, $company) => $q->where('company_id', $company)
        )
        ->when(
            $this->filters['department_id'] ?? null,
            fn ($q, $dept) => $q->where('department_id', $dept)
        );

    $data = $query
        ->select(
            DB::raw('MONTH(tanggal_penagihan) as month'),
            DB::raw('SUM(jumlah_total) as total')
        )
        ->groupBy('month')
        ->orderBy('month')
        ->pluck('total', 'month')
        ->toArray();

    $labels = [];
    $values = [];

    for ($i = 1; $i <= 12; $i++) {
        $labels[] = date('M', mktime(0, 0, 0, $i, 1));
        $values[] = $data[$i] ?? 0;
    }

    return [
        'datasets' => [
            [
                'label' => 'Amount',
                'data' => $values,
            ],
        ],
        'labels' => $labels,
    ];
}

    protected function getType(): string
    {
        return 'line';
    }
}
