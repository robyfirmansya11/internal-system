<?php

namespace App\Filament\Pages\SuratPerintahBayarReport\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\SuratPerintahBayar;
use Illuminate\Support\Facades\DB;

class DepartmentChart extends ChartWidget
{
    protected ?string $heading = 'Amount by Department';

    protected function getData(): array
    {
        $data = SuratPerintahBayar::query()
            ->join('departments', 'departments.id', '=', 'surat_perintah_bayars.department_id')
            ->select(
                'departments.nama_department',
                DB::raw('SUM(jumlah_total) as total')
            )
            ->groupBy('departments.nama_department')
            ->pluck('total', 'nama_department')
            ->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Total Amount',
                    'data' => array_values($data),
                ],
            ],
            'labels' => array_keys($data),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
