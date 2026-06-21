<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Lembur;
use Illuminate\Support\Facades\DB;

class OvertimeEmployeeChart extends ChartWidget
{
    protected ?string $heading = 'Overtime by Employee';

    protected function getData(): array
    {
        $data = Lembur::query()
            ->select('users.name', DB::raw('SUM(jumlah_jam_lembur) as total_hours'))
            ->join('users', 'lemburs.user_id', '=', 'users.id')
            ->where('status', 'approved')
            ->whereYear('tanggal_lembur', now()->year)
            ->whereMonth('tanggal_lembur', now()->month)
            ->groupBy('users.name')
            ->orderByDesc('total_hours')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Total Hours',
                    'data' => $data->pluck('total_hours'),
                ],
            ],
            'labels' => $data->pluck('name'),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}