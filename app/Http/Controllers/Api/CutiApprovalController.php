<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FormCuti;
use Illuminate\Http\Request;

class CutiApprovalController extends Controller
{
    /**
     * List pengajuan cuti yang menunggu approval dari user yang login.
     * - Superuser: melihat pengajuan bawahan langsung yang menunggu level 1
     * - HRD (Admin dengan jabatan HRD): melihat semua pengajuan yang menunggu level 2
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user->canApprove()) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses untuk melihat halaman ini.',
            ], 403);
        }

        $query = FormCuti::with(['user', 'department'])
            ->where('status', 'Pending Approval');

        if ($user->isSuperuser()) {
            $query->where('approval_level', 1)
                ->whereHas('user.profile', function ($q) use ($user) {
                    $q->where('atasan_id', $user->id);
                });
        } elseif ($user->isHRD()) {
            $query->where('approval_level', 2);
        } else {
            // Admin biasa (bukan HRD) atau role lain yang tidak relevan
            return response()->json([]);
        }

        $cutis = $query->orderBy('tanggal_mulai')
            ->get()
            ->map(fn ($c) => $this->formatApprovalItem($c));

        return response()->json($cutis);
    }

    /**
     * Approve pengajuan cuti — otomatis tahu approve sebagai atasan atau HRD
     * berdasarkan approval_level record dan role user yang login.
     */
    public function approve(Request $request, $id)
    {
        $user = $request->user();
        $cuti = FormCuti::findOrFail($id);

        $result = match (true) {
            $cuti->approval_level === 1 && $user->isSuperuser() => $cuti->approveByAtasan($user),
            $cuti->approval_level === 2 && $user->isHRD() => $cuti->approveByAdmin($user),
            default => false,
        };

        if (! $result) {
            return response()->json([
                'message' => 'Pengajuan tidak dapat disetujui. Kemungkinan sudah diproses atau Anda tidak berwenang.',
            ], 422);
        }

        return response()->json([
            'message' => 'Pengajuan cuti berhasil disetujui.',
        ]);
    }

    /**
     * Reject pengajuan cuti.
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'rejected_note' => 'required|string|max:500',
        ]);

        $user = $request->user();
        $cuti = FormCuti::findOrFail($id);

        if (! $cuti->canBeApprovedBy($user)) {
            return response()->json([
                'message' => 'Anda tidak berwenang menolak pengajuan ini.',
            ], 422);
        }

        $cuti->reject($user, $request->rejected_note);

        return response()->json([
            'message' => 'Pengajuan cuti berhasil ditolak.',
        ]);
    }

    /**
     * Format item untuk list approval — lebih detail dari list milik pemohon sendiri,
     * karena approver butuh tahu siapa pemohonnya.
     */
    private function formatApprovalItem(FormCuti $cuti): array
    {
        return [
            'id' => $cuti->id,
            'employee_name' => $cuti->user?->name,
            'department' => $cuti->department?->nama_department,
            'jenis_cuti' => $cuti->jenis_cuti,
            'tanggal_mulai' => $cuti->tanggal_mulai?->format('Y-m-d'),
            'tanggal_selesai' => $cuti->tanggal_selesai?->format('Y-m-d'),
            'formatted_mulai' => $cuti->tanggal_mulai?->format('d M Y'),
            'formatted_selesai' => $cuti->tanggal_selesai?->format('d M Y'),
            'jumlah_hari' => $cuti->jumlah_hari,
            'alasan' => $cuti->alasan,
            'status' => $cuti->status,
            'approval_level' => $cuti->approval_level,
            'created_at' => $cuti->created_at?->format('d M Y'),
        ];
    }
}
