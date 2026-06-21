<?php

namespace App\Models;

use App\Traits\HasApprovalWorkflow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SuratPerintahBayar extends Model
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
        'tanggal_penagihan',
        'tanggal_jatuhtempo',
        'no_invoice',
        'jumlah',
        'ppn',
        'pph',
        'admin',
        'jumlah_total',
        'terbilang',
        'pembayaran_tahap',
        'jumlah_lampiran',
        'keterangan',
        'informasi_transfer',
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
        'tanggal_penagihan' => 'date',
        'tanggal_jatuhtempo' => 'date',
        'jumlah' => 'float',
        'ppn' => 'float',
        'pph' => 'float',
        'admin' => 'float',
        'jumlah_total' => 'float',
        'approved_at' => 'datetime',
        'approved_manager_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    /** Perusahaan terkait SPB */
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

    /** User yang approve final (level 2) */
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
        return ! $this->isSubmitted();
    }

    /** Hanya bisa diedit kalau masih Submitted & milik sendiri */
    public function canBeEditedBy(User $user): bool
    {
        return $this->user_id === $user->id
            && $this->isSubmitted();
    }

    /** Hanya bisa dihapus kalau belum Approved & milik sendiri */
    public function canBeDeletedBy(User $user): bool
    {
        return $this->user_id === $user->id
            && ! $this->isApproved();
    }

    /*
|--------------------------------------------------------------------------
| OVERRIDE TRAIT — Approval khusus SPB
| Kolom spesifik:
| - approved_by_manager / approved_manager_at → Atasan (level 1)
| - approved_by / approved_at               → Finance Manager (level 2)
|--------------------------------------------------------------------------
*/

    /**
     * Override approveByAtasan dari trait.
     * Simpan ke approved_by_manager (bukan approved_by) agar kolom signature
     * PDF bisa membedakan atasan vs Finance Manager.
     */
    public function approveByAtasan(User $approver): bool
    {
        if ($this->approval_level !== 1) {
            return false;
        }

        if (! $this->isValidAtasan($approver)) {
            return false;
        }

        // Cek apakah atasan sekaligus Finance Manager
        if ($this->isLevel2Approver($approver)) {
            $this->update([
                'approved_by_manager' => $approver->id,
                'approved_manager_at' => now(),
                'approved_by' => $approver->id,
                'approved_at' => now(),
                'approval_level' => 3,
                'status' => 'Approved',
            ]);

            return true;
        }

        // Normal: lanjut ke Finance Manager
        $this->update([
            'approved_by_manager' => $approver->id,
            'approved_manager_at' => now(),
            'approval_level' => 2,
            'status' => 'Pending Approval',
        ]);

        return true;
    }

    /**
     * Override approveByAdmin dari trait.
     * Finance Manager approve → simpan ke approved_by / approved_at.
     */
    public function approveByAdmin(User $approver): bool
    {
        if ($this->approval_level !== 2) {
            return false;
        }

        if (! $this->isLevel2Approver($approver)) {
            return false;
        }

        $this->update([
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'approval_level' => 3,
            'status' => 'Approved',
        ]);

        return true;
    }

    public function getManagerDepartmentNameAttribute(): ?string
    {
        return $this->department?->nama_department;
    }
}
