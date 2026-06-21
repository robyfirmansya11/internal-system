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
            ->where('status', 'Approved'); // fix: kapital

        // Role restriction
        if ($auth->isUser()) {
            $query->where('user_id', $auth->id);

        } elseif ($auth->isSuperuser()) {
            // Fix: pakai atasan_id bukan department_id
            $query->whereHas('user.profile', function ($q) use ($auth) {
                $q->where('atasan_id', $auth->id);
            });
        }
        // Admin & Superadmin lihat semua

        // Form filters
        $query
            ->when(
                $request->user_id,
                fn ($q, $id) => $q->where('user_id', $id)
            )
            ->when(
                $request->department_id,
                fn ($q, $id) => $q->where('department_id', $id)
            )
            ->when(
                $request->year,
                fn ($q, $year) => $q->whereYear('tanggal_lembur', $year)
            )
            ->when(
                $request->month,
                fn ($q, $month) => $q->whereMonth('tanggal_lembur', $month)
            );

        $data = $query->orderBy('tanggal_lembur')->get();
        $totalJam = $data->sum('jumlah_jam_lembur');

        // Header info
        $nama = null;
        $department = null;
        $bulanTahun = null;

        if ($request->filled('user_id')) {
            $user = User::find($request->user_id);
            $nama = $user?->name;
            $department = $user?->departments->first()?->nama_department;
        }

        if ($request->filled('department_id')) {
            $dept = Department::find($request->department_id);
            $department = $dept?->nama_department;
        }

        // Kalau user biasa export miliknya sendiri
        if ($auth->isUser() && ! $request->filled('user_id')) {
            $nama = $auth->name;
            $department = $auth->departments->first()?->nama_department;
        }

        if ($request->filled('month') && $request->filled('year')) {
            $bulanTahun = Carbon::createFromDate($request->year, $request->month, 1)
                ->translatedFormat('F Y');
        } elseif ($request->filled('year')) {
            $bulanTahun = "Tahun {$request->year}";
        }

        // Tampilkan kolom employee kalau tidak filter per-user
        $showEmployeeCol = ! $request->filled('user_id') && ! $auth->isUser();

        $pdf = Pdf::loadView(
            'filament.pages.overtime.overtime-pdf',
            compact(
                'data',
                'totalJam',
                'nama',
                'department',
                'bulanTahun',
                'showEmployeeCol',
            )
        )->setPaper('a4', 'landscape');

        $month = $request->month ? Carbon::create()->month($request->month)->format('m') : 'all';
        $year = $request->year ?? now()->year;

        return $pdf->stream("Overtime-Report-{$year}-{$month}.pdf");
    }
}
