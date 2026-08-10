<?php

namespace App\Models;

use App\Traits\HasApprovalWorkflow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Keterlambatan extends Model
{
    use HasApprovalWorkflow;

    protected $table = 'form_keterlambatan';

    // Approval: Atasan → HRD
    const APPROVAL_LEVELS = 2;

    const LEVEL2_JABATAN = 'HRD';

    protected $fillable = [
        'user_id',
        'department_id',
        'tanggal',
        'jam_masuk',
        'alasan',
        'approval_level',
        'status',
        'approved_by_manager',
        'approved_manager_at',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_note',
        'cancelled_by',
        'cancelled_at',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'jam_masuk' => 'datetime:H:i',
        'approved_at' => 'datetime',
        'approved_manager_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function approverManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_manager');
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    public function isLocked(): bool
    {
        // Form di-lock kalau sudah Approved atau Rejected
        // Masih bisa edit saat Submitted atau Pending Approval
        return $this->isApproved() || $this->isRejected();
    }

    public function canBeEditedBy(User $user): bool
    {
        // Bisa edit kalau masih Submitted ATAU Pending Approval & milik sendiri
        return ($this->isSubmitted() || $this->isPending())
            && $this->user_id === $user->id;
    }

    public function canBeDeletedBy(User $user): bool
    {
        // Bisa hapus kalau belum Approved & milik sendiri
        return ! $this->isApproved()
            && $this->user_id === $user->id;
    }

    public function canBeViewedBy(User $user): bool
    {
        if ($user->isSuperadmin() || $user->isAdmin()) {
            return true;
        }

        if ($user->isSuperuser()) {
            return $user->departments->contains($this->department_id);
        }

        return $this->user_id === $user->id;
    }

    public function getStageLabel(): string
    {
        return match (true) {
            $this->isApproved() => 'Approved',
            $this->isRejected() => 'Rejected',
            $this->isCancelled() => 'Cancelled',
            $this->isWaitingAtasan() => 'Waiting Manager',
            $this->isWaitingAdmin() => 'Waiting HRD',
            $this->isSubmitted() => 'Submitted',
            default => 'Unknown',
        };
    }

    public function getStageColor(): string
    {
        return match (true) {
            $this->isApproved() => 'approved',
            $this->isRejected() => 'rejected',
            $this->isCancelled() => 'unknown',
            $this->isWaitingAtasan() => 'waiting',
            $this->isWaitingAdmin() => 'hrd',
            default => 'unknown',
        };
    }
}
