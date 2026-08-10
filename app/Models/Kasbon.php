<?php

namespace App\Models;

use App\Traits\HasApprovalWorkflow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Kasbon extends Model
{
    use HasApprovalWorkflow, SoftDeletes;

    /**
     * Approval: Atasan (level 1) → Finance Manager (level 2).
     */
    const APPROVAL_LEVELS = 2;

    const LEVEL2_JABATAN = 'Finance Manager';

    protected $fillable = [
        'company_id',
        'user_id',
        'department_id',
        'tanggal',
        'keterangan',
        'jumlah_dana',
        'terbilang',
        'informasi_transfer',
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
        'jumlah_dana' => 'decimal:2',
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

    /** Perusahaan terkait kasbon */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** Karyawan yang mengajukan */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Department karyawan */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /** User yang approve (level 2 / final) */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /** User yang approve sebagai atasan (level 1) */
    public function approverManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_manager');
    }

    /** User yang menolak */
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
     * Form di-lock kalau sudah tidak Submitted.
     * Menggunakan isSubmitted() dari trait.
     */
    public function isLocked(): bool
    {
        // Form di-lock hanya kalau sudah Approved, Rejected, atau Cancelled
        return $this->isApproved()
            || $this->isRejected()
            || $this->isCancelled();
    }

    /** Hanya bisa diedit kalau masih Submitted & milik sendiri */
    public function canBeEditedBy(User $user): bool
    {
        return $this->user_id === $user->id
            && ($this->isSubmitted() || $this->isPending());
    }

    /** Hanya bisa dihapus kalau belum Approved & milik sendiri */
    public function canBeDeletedBy(User $user): bool
    {
        return $this->user_id === $user->id
            && ! $this->isApproved();
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
