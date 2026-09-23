<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Keterlambatan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LatePermitController extends Controller
{
    public function index(Request $request)
    {
        $records = Keterlambatan::where('user_id', $request->user()->id)
            ->orderByDesc('tanggal')->orderByDesc('id')->paginate(20);
        $records->through(fn (Keterlambatan $record) => $this->format($record, $request));
        return response()->json($records);
    }

    public function show(Request $request, int $id)
    {
        $record = Keterlambatan::where('user_id', $request->user()->id)->findOrFail($id);
        return response()->json($this->format($record, $request));
    }

    public function store(Request $request)
    {
        $user = $request->user();
        abort_if($user->isSuperuser(), 403, 'Role ini tidak dapat membuat pengajuan izin terlambat.');
        $data = $request->validate([
            'tanggal' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'jam_masuk' => ['required', 'date_format:H:i'],
            'alasan' => ['required', 'string', 'max:5000'],
        ]);
        $department = $user->departments()->first();
        if (! $department) {
            return response()->json(['message' => 'User belum memiliki department.'], 422);
        }
        $atasan = $user->profile?->atasan;
        $selfApprove = ! $atasan || $atasan->id === $user->id;
        // Same initial workflow as Filament CreateKeterlambatan.
        $autoApproved = $selfApprove && $user->jabatan === Keterlambatan::LEVEL2_JABATAN;
        $record = Keterlambatan::create(array_merge($data, [
            'user_id' => $user->id, 'department_id' => $department->id,
            'status' => $autoApproved ? 'Approved' : 'Pending Approval',
            'approval_level' => $autoApproved ? 3 : ($selfApprove ? 2 : 1),
            'approved_by_manager' => $selfApprove ? $user->id : null,
            'approved_manager_at' => $selfApprove ? now() : null,
            'approved_by' => $autoApproved ? $user->id : null,
            'approved_at' => $autoApproved ? now() : null,
        ]));
        return response()->json([
            'message' => 'Pengajuan izin terlambat berhasil dikirim.',
            'data' => $this->format($record, $request),
        ], 201);
    }

    public function cancel(Request $request, int $id)
    {
        return DB::transaction(function () use ($request, $id) {
            $record = Keterlambatan::where('user_id', $request->user()->id)
                ->lockForUpdate()->findOrFail($id);
            if (! $record->cancel($request->user())) {
                return response()->json(['message' => 'Pengajuan ini tidak dapat dibatalkan.'], 422);
            }
            return response()->json([
                'message' => 'Pengajuan izin terlambat dibatalkan.',
                'data' => $this->format($record, $request),
            ]);
        });
    }

    protected function format(Keterlambatan $record, Request $request): array
    {
        return [
            'id' => $record->id, 'tanggal' => $record->tanggal?->format('Y-m-d'),
            'jam_masuk' => $record->jam_masuk?->format('H:i'), 'alasan' => $record->alasan,
            'status' => $record->status, 'approval_level' => $record->approval_level,
            'rejected_note' => $record->rejected_note,
            'can_cancel' => $record->canBeCancelledBy($request->user()),
            'employee_name' => $record->user?->name,
            'department_name' => $record->department?->nama_department,
            'can_approve' => $record->isPending() && (
                ($request->user()->isSuperuser() && $record->isWaitingAtasan() && $record->isValidAtasan($request->user()))
                || ($request->user()->isAdmin() && $request->user()->jabatan === Keterlambatan::LEVEL2_JABATAN && $record->isWaitingAdmin())
            ),
        ];
    }
}
