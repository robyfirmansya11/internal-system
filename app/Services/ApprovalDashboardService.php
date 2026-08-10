<?php

namespace App\Services;

use App\Models\FormCuti;
use App\Models\Kasbon;
use App\Models\Keterlambatan;
use App\Models\Lembur;
use App\Models\PerjalananDinas;
use App\Models\PermohonanStempel;
use App\Models\SuratPerintahBayar;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class ApprovalDashboardService
{
    /**
     * Daftar semua modul approval yang tampil di dashboard.
     * Sesuaikan 'route' kalau nama route resource Anda berbeda.
     */
    public static function modules(): array
    {
        return [
            [
                'model' => Keterlambatan::class,
                'label' => 'Late Working Permit',
                'icon' => 'heroicon-o-exclamation-triangle',
                'color' => 'danger',
                'route' => 'filament.admin.resources.keterlambatans.index',
                'title' => fn ($r) => 'Late Arrival — '.(
                    $r->tanggal
                        ? \Carbon\Carbon::parse($r->tanggal)->format('d M Y')
                        : ($r->keterangan ?? $r->alasan ?? '-')
                ),
            ],
            [
                'model' => Lembur::class,
                'label' => 'Overtime',
                'icon' => 'heroicon-o-clock',
                'color' => 'warning',
                'route' => 'filament.admin.resources.lemburs.index',
                'title' => fn ($r) => 'Overtime — '.number_format($r->jumlah_jam_lembur ?? 0, 1).' hrs',
            ],
            [
                'model' => FormCuti::class,
                'label' => 'Leave Request',
                'icon' => 'heroicon-o-calendar-days',
                'color' => 'info',
                'route' => 'filament.admin.resources.form-cutis.index',
                'title' => fn ($r) => ($r->jenis_cuti ?? 'Leave').' — '.($r->jumlah_hari ?? 0).' day(s)',
            ],
            [
                'model' => PerjalananDinas::class,
                'label' => 'Travel Reimbursement',
                'icon' => 'heroicon-o-briefcase',
                'color' => 'primary',
                'route' => 'filament.admin.resources.perjalanan-dinas.index',
                'title' => fn ($r) => 'Business Trip — '.\Illuminate\Support\Str::limit($r->keterangan ?? '-', 30),
            ],
            [
                'model' => PermohonanStempel::class,
                'label' => 'Stamp Application',
                'icon' => 'heroicon-o-document-text',
                'color' => 'gray',
                'route' => 'filament.admin.resources.permohonan-stempels.index',
                'title' => fn ($r) => 'Stamp Request — '.\Illuminate\Support\Str::limit($r->tujuan ?? '-', 30),
            ],
            [
                'model' => Kasbon::class,
                'label' => 'Loan Note',
                'icon' => 'heroicon-o-banknotes',
                'color' => 'success',
                'route' => 'filament.admin.resources.kasbons.index',
                'title' => fn ($r) => 'Loan Note — Rp '.number_format($r->jumlah_dana ?? 0, 0, ',', '.'),
            ],
            [
                'model' => SuratPerintahBayar::class,
                'label' => 'Payment Application',
                'icon' => 'heroicon-o-credit-card',
                'color' => 'danger',
                'route' => 'filament.admin.resources.surat-perintah-bayars.index',
                'title' => fn ($r) => 'Payment Order — '.($r->no_invoice ?? '-'),
            ],
        ];
    }

    /**
     * Peta warna Filament -> hex, dipakai lewat inline style di blade
     * (bukan class Tailwind dinamis) supaya tidak kena masalah CSS
     * purging dan tetap konsisten di semua widget dashboard.
     */
    public static function colorMap(): array
    {
        return [
            'danger' => '#ef4444',
            'info' => '#3b82f6',
            'warning' => '#f59e0b',
            'success' => '#22c55e',
            'primary' => '#f59e0b',
            'gray' => '#6b7280',
        ];
    }

    public static function colorHex(?string $color): string
    {
        return static::colorMap()[$color] ?? static::colorMap()['gray'];
    }

    protected static function urlFor(string $routeName): string
    {
        return Route::has($routeName) ? route($routeName) : '#';
    }

    /**
     * Jumlah item yang menunggu approval dari user yang sedang login,
     * per modul. Memakai scopeWaitingApprovalFrom() dari HasApprovalWorkflow.
     */
    public static function pendingCounts(User $user): Collection
    {
        return collect(static::modules())->map(function ($module) use ($user) {
            /** @var class-string $model */
            $model = $module['model'];

            $count = $model::waitingApprovalFrom($user)->count();

            return array_merge($module, [
                'count' => $count,
                'url' => static::urlFor($module['route']),
            ]);
        });
    }

    /**
     * Daftar item (gabungan semua modul) yang menunggu approval dari user
     * yang sedang login, diurutkan dari yang paling lama menunggu.
     */
    public static function pendingItems(User $user, int $limit = 8): Collection
    {
        $items = collect();

        foreach (static::modules() as $module) {
            /** @var class-string $model */
            $model = $module['model'];

            $records = $model::waitingApprovalFrom($user)
                ->with('user')
                ->latest('created_at')
                ->limit($limit)
                ->get();

            foreach ($records as $record) {
                $items->push([
                    'label' => $module['label'],
                    'icon' => $module['icon'],
                    'color' => $module['color'],
                    'title' => ($module['title'])($record),
                    'requester' => $record->user?->name ?? '-',
                    'created_at' => $record->created_at,
                    'url' => static::urlFor($module['route']),
                ]);
            }
        }

        return $items->sortBy('created_at')->take($limit)->values();
    }

    /**
     * Aktivitas terkini: pengajuan yang dibuat ATAU diproses (approve/reject)
     * oleh user yang sedang login, dari semua modul, diurutkan terbaru.
     */
    public static function recentActivity(User $user, int $limit = 8): Collection
    {
        $items = collect();

        foreach (static::modules() as $module) {
            /** @var class-string $model */
            $model = $module['model'];

            $query = $model::query()->with('user')
                ->where(function ($q) use ($user, $model) {
                    $q->where('user_id', $user->id)
                        ->orWhere('approved_by', $user->id)
                        ->orWhere('approved_by_manager', $user->id)
                        ->orWhere('rejected_by', $user->id);

                    // Beberapa modul (FormCuti) punya kolom approved_by_hrd
                    if (in_array('approved_by_hrd', (new $model)->getFillable(), true)) {
                        $q->orWhere('approved_by_hrd', $user->id);
                    }
                })
                ->latest('updated_at')
                ->limit($limit)
                ->get();

            foreach ($query as $record) {
                $status = match (true) {
                    $record->isApproved() => 'Approved',
                    $record->isRejected() => 'Rejected',
                    default => $record->status,
                };

                $items->push([
                    'label' => $module['label'],
                    'icon' => $module['icon'],
                    'color' => match ($status) {
                        'Approved' => 'success',
                        'Rejected' => 'danger',
                        default => 'warning',
                    },
                    'title' => ($module['title'])($record),
                    'requester' => $record->user?->name ?? '-',
                    'status' => $status,
                    'updated_at' => $record->updated_at,
                    'url' => static::urlFor($module['route']),
                ]);
            }
        }

        return $items->sortByDesc('updated_at')->take($limit)->values();
    }

    /**
     * Breakdown status (Pending / Approved / Rejected) bulan berjalan,
     * digabung dari semua modul — untuk donut chart.
     */
    public static function statusDistributionThisMonth(): array
    {
        $counts = [
            'Pending Approval' => 0,
            'Approved' => 0,
            'Rejected' => 0,
        ];

        foreach (static::modules() as $module) {
            /** @var class-string $model */
            $model = $module['model'];

            $rows = $model::query()
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

            foreach ($rows as $status => $total) {
                if ($status === 'Submitted') {
                    $counts['Pending Approval'] += $total;
                } elseif (isset($counts[$status])) {
                    $counts[$status] += $total;
                }
            }
        }

        return $counts;
    }
}
