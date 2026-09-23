<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Lembur;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class OvertimeReportQueryService
{
    /**
     * Single source of truth for the table, PDF, and Excel report scope.
     */
    public function build(User $viewer, array $filters = []): Builder
    {
        $query = Lembur::query()
            ->with(['user', 'department'])
            ->where('status', 'Approved');

        if ($viewer->isUser()) {
            $query->where('user_id', $viewer->id);
        } elseif ($viewer->isSuperuser()) {
            // A manager may supervise employees from several departments.
            $query->whereHas('user.profile', function (Builder $query) use ($viewer): void {
                $query->where('atasan_id', $viewer->id);
            });
        }

        return $query
            ->when(
                ! $viewer->isUser() && filled($filters['user_id'] ?? null),
                fn (Builder $query) => $query->where('user_id', $filters['user_id'])
            )
            ->when(
                filled($filters['department_id'] ?? null),
                fn (Builder $query) => $query->where('department_id', $filters['department_id'])
            )
            ->when(
                filled($filters['year'] ?? null),
                fn (Builder $query) => $query->whereYear('tanggal_lembur', $filters['year'])
            )
            ->when(
                filled($filters['month'] ?? null),
                fn (Builder $query) => $query->whereMonth('tanggal_lembur', $filters['month'])
            );
    }

    public function accessibleEmployees(User $viewer): Builder
    {
        $query = User::query()->orderBy('name');

        if ($viewer->isUser()) {
            return $query->whereKey($viewer->id);
        }

        if ($viewer->isSuperuser()) {
            return $query->whereHas('profile', function (Builder $query) use ($viewer): void {
                $query->where('atasan_id', $viewer->id);
            });
        }

        return $query;
    }

    public function accessibleDepartments(User $viewer): Builder
    {
        $query = Department::query()->orderBy('nama_department');

        if ($viewer->isSuperuser()) {
            return $query->whereHas('users.profile', function (Builder $query) use ($viewer): void {
                $query->where('atasan_id', $viewer->id);
            });
        }

        return $query;
    }
}
