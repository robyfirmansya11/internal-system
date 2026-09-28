<?php

namespace App\Models;

use App\Traits\HasApprovalWorkflow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MealClaim extends Model
{
    use HasApprovalWorkflow, SoftDeletes;

    protected $fillable = [
        'user_id', 'company_id', 'department_id', 'claim_date', 'total_amount', 'receipt_count',
        'status', 'approval_level', 'employee_note', 'receipt_received_by', 'receipt_received_at', 'verified_by',
        'verified_at', 'verification_note', 'approved_by', 'approved_at', 'paid_by', 'paid_at',
        'payment_note', 'rejected_by', 'rejected_at', 'rejected_note', 'cancelled_by', 'cancelled_at',
    ];

    protected $casts = [
        'claim_date' => 'date',
        'total_amount' => 'decimal:2',
        'receipt_received_at' => 'datetime',
        'verified_at' => 'datetime',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
        'rejected_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function receiptReceiver(): BelongsTo { return $this->belongsTo(User::class, 'receipt_received_by'); }
    public function verifier(): BelongsTo { return $this->belongsTo(User::class, 'verified_by'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function payer(): BelongsTo { return $this->belongsTo(User::class, 'paid_by'); }
    public function rejector(): BelongsTo { return $this->belongsTo(User::class, 'rejected_by'); }
    public function cancelledBy(): BelongsTo { return $this->belongsTo(User::class, 'cancelled_by'); }
    public function items(): HasMany { return $this->hasMany(MealClaimItem::class); }

    public function recalculateTotals(): void
    {
        $this->updateQuietly([
            'total_amount' => $this->items()->sum('amount'),
            'receipt_count' => $this->items()->count(),
        ]);
    }

    public function canBeEditedBy(User $user): bool
    {
        return $this->user_id === $user->id && in_array($this->status, ['Submitted', 'Returned'], true);
    }

    public function canBeDeletedBy(User $user): bool
    {
        return $this->user_id === $user->id && in_array($this->status, ['Submitted', 'Returned'], true);
    }

    public function canBeCancelledBy(User $user): bool
    {
        return $this->user_id === $user->id && in_array($this->status, ['Submitted', 'Receipt Received', 'Returned'], true);
    }

    public function canBeManagedBy(User $user): bool
    {
        return $user->isFinanceOperations() && $this->user_id !== $user->id;
    }

    public function canBeApprovedBy(User $user): bool
    {
        return $this->status === 'Verified'
            && $this->user_id !== $user->id
            && $user->isFinanceManager();
    }

    public function canBePaidBy(User $user): bool
    {
        return $this->status === 'Approved'
            && $this->user_id !== $user->id
            && $user->isFinanceAccountingTaxMember();
    }

    public function receiveReceipts(User $user): bool
    {
        if (! $this->canBeManagedBy($user) || $this->status !== 'Submitted') {
            return false;
        }

        $before = $this->status;
        $this->update([
            'status' => 'Receipt Received',
            'approval_level' => 0,
            'receipt_received_by' => $user->id,
            'receipt_received_at' => now(),
        ]);
        $this->recordApprovalHistory($user, 'Original receipts received', $before);

        return true;
    }

    public function verify(User $user, ?string $note = null): bool
    {
        if (! $this->canBeManagedBy($user) || ! in_array($this->status, ['Submitted', 'Receipt Received'], true)) {
            return false;
        }

        $before = $this->status;
        $this->update([
            'status' => 'Verified',
            'approval_level' => 1,
            'verified_by' => $user->id,
            'verified_at' => now(),
            'verification_note' => $note,
        ]);
        $this->recordApprovalHistory($user, 'Verified', $before, $note);

        return true;
    }

    public function approve(User $user): bool
    {
        if (! $this->canBeApprovedBy($user)) {
            return false;
        }

        $before = $this->status;
        $this->update(['status' => 'Approved', 'approval_level' => 2, 'approved_by' => $user->id, 'approved_at' => now()]);
        $this->recordApprovalHistory($user, 'Approved', $before);

        return true;
    }

    public function markPaid(User $user, ?string $note = null): bool
    {
        if (! $this->canBePaidBy($user)) {
            return false;
        }

        $before = $this->status;
        $this->update(['status' => 'Paid', 'approval_level' => 3, 'paid_by' => $user->id, 'paid_at' => now(), 'payment_note' => $note]);
        $this->recordApprovalHistory($user, 'Paid', $before, $note);

        return true;
    }

    public function returnForCorrection(User $user, string $note): bool
    {
        if (! $this->canBeManagedBy($user) || in_array($this->status, ['Approved', 'Paid', 'Rejected', 'Cancelled'], true)) {
            return false;
        }

        $before = $this->status;
        $this->update(['status' => 'Returned', 'approval_level' => 0, 'verification_note' => $note]);
        $this->recordApprovalHistory($user, 'Returned for correction', $before, $note);

        return true;
    }

    public function rejectClaim(User $user, string $note): bool
    {
        if (($this->canBeManagedBy($user) || $this->canBeApprovedBy($user)) && ! in_array($this->status, ['Approved', 'Paid', 'Rejected', 'Cancelled'], true)) {
            $before = $this->status;
            $this->update([
                'status' => 'Rejected', 'approval_level' => -1, 'rejected_by' => $user->id,
                'rejected_at' => now(), 'rejected_note' => $note,
            ]);
            $this->recordApprovalHistory($user, 'Rejected', $before, $note);

            return true;
        }

        return false;
    }

    public function cancelClaim(User $user): bool
    {
        if (! $this->canBeCancelledBy($user)) {
            return false;
        }

        $before = $this->status;
        $this->update(['status' => 'Cancelled', 'approval_level' => -2, 'cancelled_by' => $user->id, 'cancelled_at' => now()]);
        $this->recordApprovalHistory($user, 'Cancelled', $before);

        return true;
    }
}
