<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Route;

class QuickActionsWidget extends Widget
{
    protected string $view = 'filament.widgets.quick-actions-widget';

    protected int|string|array $columnSpan = 12;

    public function getActions(): array
    {
        $actions = [
            [
                'label' => 'Late Working Permit',
                'icon' => 'heroicon-o-exclamation-triangle',
                'color' => 'danger',
                'route' => 'filament.admin.resources.keterlambatans.create',
            ],
            [
                'label' => 'Leave Request',
                'icon' => 'heroicon-o-calendar-days',
                'color' => 'info',
                'route' => 'filament.admin.resources.form-cutis.create',
            ],
            [
                'label' => 'Request Overtime',
                'icon' => 'heroicon-o-clock',
                'color' => 'warning',
                'route' => 'filament.admin.resources.lemburs.create',
            ],
            [
                'label' => 'Loan Note',
                'icon' => 'heroicon-o-banknotes',
                'color' => 'success',
                'route' => 'filament.admin.resources.kasbons.create',
            ],
            [
                'label' => 'Travel Reimbursement',
                'icon' => 'heroicon-o-briefcase',
                'color' => 'primary',
                'route' => 'filament.admin.resources.perjalanan-dinas.create',
            ],
            [
                'label' => 'Payment Application Letter',
                'icon' => 'heroicon-o-credit-card',
                'color' => 'danger',
                'route' => 'filament.admin.resources.surat-perintah-bayars.create',
            ],
            [
                'label' => 'Stamp Application Letter',
                'icon' => 'heroicon-o-document-text',
                'color' => 'gray',
                'route' => 'filament.admin.resources.permohonan-stempels.create',
            ],
            [
                'label' => 'Register Letter',
                'icon' => 'heroicon-o-envelope',
                'color' => 'info',
                'route' => 'filament.admin.resources.register-surats.create',
            ],
        ];

        return collect($actions)
            ->filter(fn ($a) => Route::has($a['route']))
            ->map(fn ($a) => array_merge($a, ['url' => route($a['route'])]))
            ->values()
            ->toArray();
    }
}
