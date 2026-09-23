<?php

namespace App\Http\Controllers\Api;

use App\Models\Keterlambatan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LatePermitApprovalController extends LatePermitController
{
    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user->isSuperuser() || ($user->isAdmin() && $user->jabatan === Keterlambatan::LEVEL2_JABATAN), 403);
        $query = Keterlambatan::with(['user.profile', 'department'])->where('status', 'Pending Approval');
        if ($user->isSuperuser()) {
            $query->where('approval_level', 1)->whereHas('user.profile', fn ($q) => $q->where('atasan_id', $user->id));
        } else {
            $query->where('approval_level', 2);
        }
        $records = $query->orderBy('tanggal')->orderBy('id')->paginate(20);
        $records->through(fn ($record) => $this->format($record, $request));
        return response()->json($records);
    }

    public function approve(Request $request, int $id)
    {
        return $this->decide($request, $id, false);
    }

    public function reject(Request $request, int $id)
    {
        $request->validate(['rejected_note' => ['required', 'string', 'max:5000']]);
        return $this->decide($request, $id, true);
    }

    private function decide(Request $request, int $id, bool $reject)
    {
        return DB::transaction(function () use ($request, $id, $reject) {
            $record = Keterlambatan::lockForUpdate()->findOrFail($id);
            $user = $request->user();
            $manager = $user->isSuperuser() && $record->isValidAtasan($user);
            $hr = $user->isAdmin() && $user->jabatan === Keterlambatan::LEVEL2_JABATAN;
            abort_unless($manager || $hr, 403, 'You do not have permission to process this request.');
            abort_unless($record->isPending(), 409, 'This request has already been processed. Refresh the list.');
            abort_unless(($manager && $record->isWaitingAtasan()) || ($hr && $record->isWaitingAdmin()), 409, 'This request is no longer awaiting your approval. Refresh the list.');
            $success = $reject
                ? $record->reject($user, $request->input('rejected_note'))
                : ($record->isWaitingAtasan() ? $record->approveByAtasan($user) : $record->approveByAdmin($user));
            abort_unless($success, 409, 'Unable to process this request. Refresh the list.');
            return response()->json(['data' => $this->format($record->fresh(), $request)]);
        });
    }
}
