<?php

namespace App\Http\Controllers;

use App\Exports\OvertimeReportExport;
use App\Models\User;
use App\Services\OvertimeReportQueryService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class OvertimeReportController extends Controller
{
    public function exportExcel(Request $request)
    {
        $auth = auth()->user();
        $filters = $request->only(['year', 'month', 'user_id', 'department_id']);
        $reportQueries = app(OvertimeReportQueryService::class);
        $data = $reportQueries->build($auth, $filters)
            ->orderBy('tanggal_lembur')
            ->get();

        [$nama, $department, $bulanTahun] = $this->reportHeader($request, $auth, $reportQueries);
        $month = (int) ($filters['month'] ?? now()->month);
        $year = (int) ($filters['year'] ?? now()->year);

        return Excel::download(
            new OvertimeReportExport($data, $nama, $department, $bulanTahun),
            sprintf('Laporan Lembur %04d-%02d.xlsx', $year, $month)
        );
    }

    public function exportPdf(Request $request)
    {
        $auth = auth()->user();

        $filters = $request->only(['year', 'month', 'user_id', 'department_id']);
        $reportQueries = app(OvertimeReportQueryService::class);
        $query = $reportQueries->build($auth, $filters);

        $data = $query->orderBy('tanggal_lembur')->get();
        $totalJam = $data->sum('jumlah_jam_lembur');

        /*
        |--------------------------------------------------------------------------
        | HEADER INFO untuk PDF
        |--------------------------------------------------------------------------
        */
        [$nama, $department, $bulanTahun] = $this->reportHeader($request, $auth, $reportQueries);

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

    private function reportHeader(
        Request $request,
        User $auth,
        OvertimeReportQueryService $reportQueries,
    ): array {
        $nama = null;
        $department = null;

        if ($request->filled('user_id') && ! $auth->isUser()) {
            $user = $reportQueries->accessibleEmployees($auth)
                ->with('departments')
                ->whereKey($request->user_id)
                ->first();
            $nama = $user?->name;
            $department = $user?->departments->first()?->nama_department;
        }

        if ($request->filled('department_id')) {
            $dept = $reportQueries->accessibleDepartments($auth)
                ->whereKey($request->department_id)
                ->first();
            $department = $dept?->nama_department;
        }

        if ($auth->isUser()) {
            $nama = $auth->name;
            $department = $auth->departments->first()?->nama_department;
        }

        if ($request->filled('month') && $request->filled('year')) {
            $bulanTahun = Carbon::createFromDate(
                (int) $request->year,
                (int) $request->month,
                1
            )->translatedFormat('F Y');
        } elseif ($request->filled('year')) {
            $bulanTahun = 'Tahun '.$request->year;
        } else {
            $bulanTahun = now()->translatedFormat('F Y');
        }

        return [$nama, $department, $bulanTahun];
    }
}
