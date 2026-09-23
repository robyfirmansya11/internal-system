<?php

namespace App\Filament\Pages\SuratPerintahBayarReport\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\SuratPerintahBayar;
use Illuminate\Support\Facades\DB;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class StatusChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Amount by Status';

    protected function getData(): array
    {
        $data = SuratPerintahBayar::query()
            ->when($this->filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate('tanggal_penagihan', '>=', $date))
            ->when($this->filters['date_until'] ?? null, fn ($q, $date) => $q->whereDate('tanggal_penagihan', '<=', $date))
            ->when($this->filters['month'] ?? null, fn ($q, $month) => $q->whereMonth('tanggal_penagihan', $month))
            ->when($this->filters['year'] ?? null, fn ($q, $year) => $q->whereYear('tanggal_penagihan', $year))
            ->when($this->filters['company_id'] ?? null, fn ($q, $company) => $q->where('company_id', $company))
            ->when($this->filters['department_id'] ?? null, fn ($q, $department) => $q->where('department_id', $department))
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
