<?php

namespace App\Filament\Pages\SuratPerintahBayarReport\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\SuratPerintahBayar;
use Illuminate\Support\Facades\DB;

class CompanyChart extends ChartWidget
{
    protected ?string $heading = 'Amount by Company';

    protected function getData(): array
    {
        $data = SuratPerintahBayar::query()
            ->join('companies', 'companies.id', '=', 'surat_perintah_bayars.company_id')
            ->select(
                'companies.nama',
                DB::raw('SUM(jumlah_total) as total')
            )
            ->groupBy('companies.nama')
            ->pluck('total', 'nama')
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
