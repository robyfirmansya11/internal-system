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

    const LEVEL2_JABATAN = null; // tidak ada level 2

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
        'rejected_note',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'tanggal_surat' => 'date',
        'tanggal_stempel' => 'date',
        'approved_at' => 'datetime',
        'approved_manager_at' => 'datetime',
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
        return ! $this->isSubmitted();
    }

    public function canBeEditedBy(User $user): bool
    {
        return $this->user_id === $user->id
            && $this->isSubmitted();
    }

    public function canBeDeletedBy(User $user): bool
    {
        return $this->user_id === $user->id
            && $this->isSubmitted();
    }
}
