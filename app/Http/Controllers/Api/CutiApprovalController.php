<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FormCuti;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CutiApprovalController extends Controller
{
    /**
     * List pengajuan cuti yang menunggu approval dari user yang login.
     * - Atasan: melihat pengajuan bawahan langsung yang menunggu level 1.
     * - Finance Manager: melihat pengajuan Manager yang menunggu tahap Finance.
     * - HRD: melihat pengajuan yang sudah mencapai tahap akhir.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user->canApprove()
            && ! $user->isSuperadmin()
            && ! $user->isFinanceManager()
            && ! $user->isHRD()
        ) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses untuk melihat halaman ini.',
            ], 403);
        }

        $query = FormCuti::with(['user.profile', 'department'])
            ->waitingApprovalFrom($user);

        $cutis = $query->orderBy('tanggal_mulai')
            ->get()
            ->map(fn ($c) => $this->formatApprovalItem($c, $request));

        return response()->json($cutis);
    }

    /**
     * Approve pengajuan cuti — otomatis tahu approve sebagai atasan, Finance Manager, atau HRD
     * berdasarkan approval_level record dan role user yang login.
     */
    public function approve(Request $request, $id)
    {
        return $this->decide($request, $id, false);
    }

    /**
     * Reject pengajuan cuti.
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'rejected_note' => 'required|string|max:500',
        ]);

        return $this->decide($request, $id, true);
    }

    private function canApprove(FormCuti $cuti, Request $request): bool
    {
        $user = $request->user();
        return $cuti->isPending() && (
            ($user->isSuperuser() && $cuti->isWaitingAtasan() && $cuti->isValidAtasan($user))
            || ($cuti->isWaitingFinanceManager() && $cuti->isFinanceManagerApprover($user))
            || ($user->isHRD() && $cuti->isWaitingAdmin())
        );
    }

    private function decide(Request $request, int $id, bool $reject)
    {
        return DB::transaction(function () use ($request, $id, $reject) {
            $cuti = FormCuti::lockForUpdate()->findOrFail($id);
            abort_unless($cuti->isPending(), 409, 'This request has already been processed. Refresh the list.');
            $user = $request->user();
            abort_unless($reject ? $cuti->canBeApprovedBy($user) : $this->canApprove($cuti, $request), 403, 'You do not have permission to process this approval stage.');
            $result = $reject ? $cuti->reject($user, $request->rejected_note) : match (true) {
                $cuti->isWaitingAtasan() => $cuti->approveByAtasan($user),
                $cuti->isWaitingFinanceManager() => $cuti->approveByFinanceManager($user),
                $cuti->isWaitingAdmin() => $cuti->approveByAdmin($user),
                default => false,
            };
            abort_unless($result, 409, 'Unable to process this request. Refresh the list.');
            return response()->json(['message' => $reject ? 'Leave request rejected.' : 'Leave request approved.']);
        });
    }

    /**
     * Format item untuk list approval — lebih detail dari list milik pemohon sendiri,
     * karena approver butuh tahu siapa pemohonnya.
     */
    private function formatApprovalItem(FormCuti $cuti, Request $request): array
    {
        return [
            'id' => $cuti->id,
            'can_approve' => $this->canApprove($cuti, $request),
            'can_reject' => $cuti->isPending() && $cuti->canBeApprovedBy($request->user()),
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
