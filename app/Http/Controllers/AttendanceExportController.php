<?php

namespace App\Http\Controllers;

use App\Exports\AttendanceExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class AttendanceExportController extends Controller
{
    public function export(Request $request)
    {
        // Hanya HRD & Superadmin
        $user = auth()->user();

        if (! ($user->isSuperadmin() || $user->isHRD())) {
            abort(403);
        }

        $month = (int) $request->month;
        $year = (int) $request->year;
        $userId = $request->user_id ? (int) $request->user_id : null;
        $departmentId = $request->department_id ? (int) $request->department_id : null;
        $filename = $request->filename ?? "Rekap-Absensi-{$month}-{$year}.xlsx";

        return Excel::download(
            new AttendanceExport($month, $year, $userId, $departmentId),
            $filename
        );
    }
}
