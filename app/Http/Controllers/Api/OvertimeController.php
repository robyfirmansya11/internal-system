<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lembur;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OvertimeController extends Controller
{
    public function index(Request $request)
    {
        $records = Lembur::with(['user.profile', 'department'])->where('user_id', $request->user()->id)
            ->orderByDesc('tanggal_lembur')->orderByDesc('id')->paginate(20);
        $records->through(fn (Lembur $record) => $this->format($record, $request));

        return response()->json($records);
    }

    public function show(Request $request, int $id)
    {
        $record = Lembur::where('user_id', $request->user()->id)->findOrFail($id);

        return response()->json($this->format($record, $request));
    }

    public function store(Request $request)
    {
        $user = $request->user();
        abort_if($user->isSuperuser(), 403, 'Role ini tidak dapat membuat pengajuan lembur.');
        $data = $request->validate([
            'bulan_lembur' => ['required', 'date_format:Y-m'],
            'tanggal_lembur' => ['required', 'date_format:Y-m-d'],
            'mulai_kerja' => ['required', 'date_format:H:i'],
            'selesai_kerja' => ['required', 'date_format:H:i', 'after:mulai_kerja'],
            'mulai_lembur' => ['required', 'date_format:H:i'],
            'selesai_lembur' => ['required', 'date_format:H:i', 'after:mulai_lembur'],
            'uang_makan' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'uraian_pekerjaan' => ['required', 'string', 'max:5000'],
        ]);
        $department = $user->departments()->first();
        if (! $department) {
            return response()->json(['message' => 'User belum memiliki department.'], 422);
        }
        $atasan = $user->profile?->atasan;
        if ($user->jabatan === Lembur::LEVEL2_JABATAN && ! $atasan) {
            return response()->json(['message' => 'Pengajuan lembur HRD memerlukan atasan langsung. Silakan hubungi administrator.'], 422);
        }
        // Match CreateLembur: users without a manager go directly to HRD.
        $selfApprove = ! $atasan || $atasan->id === $user->id;
        $data['jumlah_jam_lembur'] = round(
            Carbon::createFromFormat('H:i', $data['mulai_lembur'])
                ->diffInMinutes(Carbon::createFromFormat('H:i', $data['selesai_lembur'])) / 60,
            2
        );
        $record = Lembur::create(array_merge($data, [
            'user_id' => $user->id,
            'department_id' => $department->id,
            'status' => 'Pending Approval',
            'approval_level' => $selfApprove ? 2 : 1,
            'approved_by_manager' => $selfApprove ? $user->id : null,
            'approved_manager_at' => $selfApprove ? now() : null,
        ]));

        return response()->json([
            'message' => 'Pengajuan lembur berhasil dikirim.',
            'data' => $this->format($record, $request),
        ], 201);
    }

    public function cancel(Request $request, int $id)
    {
        return DB::transaction(function () use ($request, $id) {
            $record = Lembur::where('user_id', $request->user()->id)
                ->lockForUpdate()->findOrFail($id);
            if (! $record->cancel($request->user())) {
                return response()->json(['message' => 'Pengajuan ini tidak dapat dibatalkan.'], 422);
            }

            return response()->json([
                'message' => 'Pengajuan lembur berhasil dibatalkan.',
                'data' => $this->format($record, $request),
            ]);
        });
    }

    protected function format(Lembur $record, Request $request): array
    {
        return [
            'id' => $record->id,
            'bulan_lembur' => $record->bulan_lembur,
            'tanggal_lembur' => $record->tanggal_lembur?->format('Y-m-d'),
            'mulai_kerja' => $record->mulai_kerja?->format('H:i'),
            'selesai_kerja' => $record->selesai_kerja?->format('H:i'),
            'mulai_lembur' => $record->mulai_lembur?->format('H:i'),
            'selesai_lembur' => $record->selesai_lembur?->format('H:i'),
            'jumlah_jam_lembur' => (float) $record->jumlah_jam_lembur,
            'uang_makan' => $record->uang_makan === null ? null : (float) $record->uang_makan,
            'uraian_pekerjaan' => $record->uraian_pekerjaan,
            'status' => $record->status,
            'approval_level' => $record->approval_level,
            'rejected_note' => $record->rejected_note,
            'can_cancel' => $record->canBeCancelledBy($request->user()),
            'employee_name' => $record->user?->name,
            'department_name' => $record->department?->nama_department,
            'can_approve' => $record->isPending() && (
                ($request->user()->isSuperuser() && $record->isWaitingAtasan() && $record->isValidAtasan($request->user()))
                || ($request->user()->isHRD() && $record->isWaitingAdmin())
            ),
        ];
    }
}
