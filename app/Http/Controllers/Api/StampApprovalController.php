<?php
namespace App\Http\Controllers\Api;

use App\Models\PermohonanStempel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StampApprovalController extends StampApplicationController
{
    public function index(Request $request)
    {
        $user = $request->user();
        $finance = $user->jabatan === PermohonanStempel::LEVEL2_JABATAN;
        abort_unless($user->canApprove() || $finance, 403);
        $records = PermohonanStempel::with(['company', 'user.profile'])
            ->where('status', 'Pending Approval')
            ->where(function ($q) use ($user, $finance) {
                $q->whereRaw('1 = 0');
                if ($user->canApprove()) {
                    $q->orWhere(fn ($q) => $q->where('approval_level', 1)
                        ->whereHas('user.profile', fn ($q) => $q->where('atasan_id', $user->id)));
                }
                if ($finance) {
                    $q->orWhere(fn ($q) => $q->where('approval_level', 2)->where('user_id', '!=', $user->id));
                }
            })->orderBy('created_at')->orderBy('id')->paginate(20);
        $records->through(fn ($r) => $this->format($r, $request));
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
            $record = PermohonanStempel::lockForUpdate()->findOrFail($id);
            $user = $request->user();
            $manager = $user->canApprove() && $record->isValidAtasan($user);
            $finance = $user->jabatan === PermohonanStempel::LEVEL2_JABATAN && $record->user_id !== $user->id;
            abort_unless($manager || $finance, 403, 'You do not have permission to process this request.');
            abort_unless($record->isPending(), 409, 'This request has already been processed. Refresh the list.');
            abort_unless(($manager && $record->isWaitingAtasan()) || ($finance && $record->isWaitingAdmin()), 409, 'This request is no longer awaiting your approval. Refresh the list.');
            $success = $reject ? $record->reject($user, $request->input('rejected_note'))
                : ($record->isWaitingAtasan() ? $record->approveByAtasan($user) : $record->approveByAdmin($user));
            abort_unless($success, 409, 'Unable to process this request. Refresh the list.');
            return response()->json(['data' => $this->format($record->fresh(), $request)]);
        });
    }
}
