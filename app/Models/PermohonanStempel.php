<?php

namespace App\Models;

use App\Traits\HasApprovalWorkflow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PermohonanStempel extends Model
{
    use HasApprovalWorkflow, SoftDeletes;

    /**
     * Approval hanya 1 level — Atasan langsung saja.
     * Tidak perlu HRD atau Finance Manager.
     */
    const APPROVAL_LEVELS = 1;

    const LEVEL2_JABATAN = 'Finance Manager';

    protected $fillable = [
        'company_id',
        'user_id',
        'department_id',
        'tanggal',
        'tujuan',
        'keterangan',
        'tanggal_surat',
        'tanggal_stempel',
        'nomor_surat',
        'ditandatangani_oleh',
        'lampiran',
        'status',
        'approval_level',
        'approved_by',
        'approved_at',
        'approved_by_manager',
        'approved_manager_at',
        'rejected_by',
        'rejected_at',
        'rejected_note',
        'cancelled_by',
        'cancelled_at',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'tanggal_surat' => 'date',
        'tanggal_stempel' => 'date',
        'approved_at' => 'datetime',
        'approved_manager_at' => 'datetime',
        'rejected_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

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

    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Form di-lock kalau sudah tidak dalam status Submitted.
     * Menggunakan isSubmitted() dari trait.
     */
    public function isLocked(): bool
    {
        // Form di-lock hanya kalau sudah Approved, Rejected, atau Cancelled
        return $this->isApproved()
            || $this->isRejected()
            || $this->isCancelled();
    }

    public function canBeEditedBy(User $user): bool
    {
        return $this->user_id === $user->id
            && ($this->isSubmitted() || $this->isPending());
    }

    public function canBeDeletedBy(User $user): bool
    {
        return $this->user_id === $user->id
            && $this->isSubmitted();
    }

    /**
     * Pemohon hanya dapat membatalkan sebelum permohonan ditandatangani
     * maupun ditolak.
     */
    public function canBeCancelledBy(User $user): bool
    {
        return $this->user_id === $user->id
            && ! $this->isApproved()
            && ! $this->isRejected()
            && ! $this->isCancelled();
    }

    /**
     * Lindungi juga di level model, bukan hanya menyembunyikan tombol UI.
     */
    public function cancel(User $user): bool
    {
        if (! $this->canBeCancelledBy($user)) {
            return false;
        }

        $this->update([
            'status' => 'Cancelled',
            'cancelled_by' => $user->id,
            'cancelled_at' => now(),
        ]);

        return true;
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
