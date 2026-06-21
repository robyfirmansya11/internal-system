<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Keterlambatan;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Enums\Role;
use Illuminate\Support\Facades\Auth;


class LateWorkingReportController extends Controller
{
 public function exportPdf(Request $request)
{
    $user = Auth::user();

        if ($user->level === Role::User) {
        $request->merge([
            'user_id' => $user->id
        ]);
    }

    $query = Keterlambatan::with(['user', 'department']);

    // 🔐 FILTER BERDASARKAN ROLE
    if ($user->level === Role::User) {
        $query->where('user_id', $user->id);
    }

    if ($user->level === Role::Superuser) {
        $query->whereIn(
            'department_id',
            $user->departments()->pluck('departments.id')
        );
    }

    // ADMIN & SUPERADMIN = bebas (tidak difilter)

    // 🔍 FILTER DARI FORM
    if ($request->user_id) {
        $query->where('user_id', $request->user_id);
    }

    if ($request->bulan) {
        $query->whereMonth('tanggal', $request->bulan);
    }

    if ($request->tahun) {
        $query->whereYear('tanggal', $request->tahun);
    }

    $data = $query->orderBy('tanggal')->get();

     $logoPath = public_path('ASI.PNG'); // sesuaikan nama file logo kamu
    $logoBase64 = base64_encode(file_get_contents($logoPath));
    $logoMime = 'image/png'; // atau image/jpeg, image/svg+xml

    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
        'pdf.late-working-report',
        compact('data', 'logoBase64', 'logoMime')
    )->setPaper('A4', 'landscape');

    return $pdf->stream('late-working-report.pdf');
}
}
