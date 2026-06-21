<?php

namespace App\Filament\Widgets;

use App\Enums\Role;
use App\Models\Keterlambatan;
use App\Models\FormCuti;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ApprovalSummaryWidget extends BaseWidget
{
    protected ?string $heading = 'Approval Summary';

protected function getStats(): array
{
    $user = auth()->user();
    $stats = [];

    // =====================
    // Late Working Permit - Manager
    // =====================
    if ($user->level === Role::Superuser) {

        $count = Keterlambatan::whereIn(
                'department_id',
                $user->departments()->pluck('departments.id')
            )
            ->where('approval_level', 0)
            ->count();

        $stats[] = Stat::make('Late Working Permits', $count)
            ->description(
                $count > 0
                    ? 'Waiting Manager Approval'
                    : 'No pending approvals'
            )
            ->icon('heroicon-o-clock')
            ->color(
                $count > 0
                    ? 'warning'
                    : 'gray'
            )
            ->url(route('filament.admin.resources.keterlambatans.index'));
    }

    // =====================
    // Late Working Permit - HRD
    // =====================
    if ($user->level === Role::Admin) {

        $count = Keterlambatan::where('approval_level', 1)->count();

        $stats[] = Stat::make('Late Working Permits', $count)
            ->description(
                $count > 0
                    ? 'Waiting HRD Approval'
                    : 'No pending approvals'
            )
            ->icon('heroicon-o-user-group')
            ->color(
                $count > 0
                    ? 'info'
                    : 'gray'
            )
            ->url(route('filament.admin.resources.keterlambatans.index'));
    }

            // =====================================================
        // Leave Requests - Manager
        // =====================================================
        if ($user->level === Role::Superuser) {

            $count = FormCuti::whereIn(
                    'department_id',
                    $user->departments()->pluck('departments.id')
                )
                ->where('approval_level', 0)
                ->count();

            $stats[] = Stat::make('Leave Requests', $count)
                ->description(
                    $count > 0
                        ? 'Waiting Manager Approval'
                        : 'No pending approvals'
                )
                ->icon('heroicon-o-calendar-days')
                ->color($count > 0 ? 'warning' : 'gray')
                ->url(route('filament.admin.resources.form-cutis.index'));
        }

        // =====================================================
        // Leave Requests - HRD
        // =====================================================
        if ($user->level === Role::Admin) {

            $count = FormCuti::where('approval_level', 1)->count();

            $stats[] = Stat::make('Leave Requests', $count)
                ->description(
                    $count > 0
                        ? 'Waiting HRD Approval'
                        : 'No pending approvals'
                )
                ->icon('heroicon-o-calendar-days')
                ->color($count > 0 ? 'info' : 'gray')
                ->url(route('filament.admin.resources.form-cutis.index'));
        }

    return $stats;
}


}
