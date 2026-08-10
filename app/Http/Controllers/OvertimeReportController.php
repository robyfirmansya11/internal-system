<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Department;
use App\Models\Lembur;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OvertimeReportController extends Controller
{
    public function exportPdf(Request $request)
    {
        $auth = auth()->user();

        $query = Lembur::with(['user', 'department'])
            ->where('status', 'Approved');

        /*
        |--------------------------------------------------------------------------
        | ROLE RESTRICTION
        | - User      → hanya miliknya sendiri, tidak bisa lihat orang lain
        | - Superuser → hanya bawahan langsung (via atasan_id)
        | - Admin & Superadmin → semua data
        |--------------------------------------------------------------------------
        */
        if ($auth->isUser()) {
            // User hanya bisa lihat data dirinya sendiri — paksa user_id ke auth->id
            $query->where('user_id', $auth->id);

        } elseif ($auth->isSuperuser()) {
            $query->whereHas('user.profile', function ($q) use ($auth) {
                $q->where('atasan_id', $auth->id);
            });
        }

        /*
        |--------------------------------------------------------------------------
        | FORM FILTERS
        | Catatan: user role tidak bisa override user_id (sudah dikunci di atas)
        |--------------------------------------------------------------------------
        */
        if (! $auth->isUser()) {
            // Hanya non-user yang bisa filter by user_id
            $query->when(
                $request->user_id,
                fn ($q, $id) => $q->where('user_id', $id)
            );
        }

        $query
            ->when(
                $request->department_id,
                fn ($q, $id) => $q->where('department_id', $id)
            )
            ->when(
                $request->year,
                fn ($q, $year) => $q->whereYear('tanggal_lembur', (int) $year)
            )
            ->when(
                $request->month,
                fn ($q, $month) => $q->whereMonth('tanggal_lembur', (int) $month)
            );

        $data = $query->orderBy('tanggal_lembur')->get();
        $totalJam = $data->sum('jumlah_jam_lembur');

        /*
        |--------------------------------------------------------------------------
        | HEADER INFO untuk PDF
        |--------------------------------------------------------------------------
        */
        $nama = null;
        $department = null;
        $bulanTahun = null;

        // Nama dari filter user_id
        if ($request->filled('user_id') && ! $auth->isUser()) {
            $user = User::find($request->user_id);
            $nama = $user?->name;
            $department = $user?->departments->first()?->nama_department;
        }

        // Nama dari filter department_id
        if ($request->filled('department_id')) {
            $dept = Department::find($request->department_id);
            $department = $dept?->nama_department;
        }

        // Kalau user biasa — paksa nama sendiri
        if ($auth->isUser()) {
            $nama = $auth->name;
            $department = $auth->departments->first()?->nama_department;
        }

        // Format bulan/tahun untuk header PDF
        // Fix: cast ke int dulu sebelum dipakai Carbon
        if ($request->filled('month') && $request->filled('year')) {
            $bulanTahun = Carbon::createFromDate(
                (int) $request->year,
                (int) $request->month,
                1
            )->translatedFormat('F Y');

        } elseif ($request->filled('year')) {
            $bulanTahun = 'Tahun '.$request->year;
        }

        // Kolom employee: tampil kalau tidak filter per-user & bukan role User
        $showEmployeeCol = ! $request->filled('user_id') && ! $auth->isUser();

        $pdf = Pdf::loadView(
            'filament.pages.overtime.overtime-pdf',
            compact(
                'data',
                'totalJam',
                'nama',
                'department',
                'showEmployeeCol',
            ) + [
                'bulan_tahun' => $bulanTahun,    // ← snake_case, sesuai blade
                'bulan' => $request->filled('month')
                    ? Carbon::createFromDate(1, (int) $request->month, 1)->translatedFormat('F')
                    : null,
                'tahun' => $request->year ?? null,
                'total_records' => $data->count(),
            ]
        )->setPaper('a4', 'landscape');
        // Nama file PDF
        $month = $request->filled('month')
            ? str_pad((int) $request->month, 2, '0', STR_PAD_LEFT)
            : 'all';
        $year = $request->year ?? now()->year;

        return $pdf->stream("Overtime-Report-{$year}-{$month}.pdf");
    }
}
