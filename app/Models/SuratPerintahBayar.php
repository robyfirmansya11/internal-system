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
        'rejected_at',
        'rejected_note',
        'cancelled_by',
        'cancelled_at',
        'paid_by',
        'paid_at',
        'customer',
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
        'rejected_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'paid_at' => 'datetime',

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

    /** Hanya bisa dihapus kalau belum Approved dan belum Rejected, & milik sendiri */
    public function canBeDeletedBy(User $user): bool
    {
        return $this->user_id === $user->id
            && ! $this->isApproved()
            && ! $this->isRejected();
    }

    /** User yang membatalkan pengajuan */
    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
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

    /*
|--------------------------------------------------------------------------
| PAID STATUS
|--------------------------------------------------------------------------
*/

    public function isPaid(): bool
    {
        return $this->status === 'Paid';
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function isFatDepartment(): bool
    {
        $fatNames = [
            'FAT',
            'Finance, Accounting dan Tax',
            'Finance, Accounting & Tax',
            'Finance Accounting Tax',
            'FAT Department',
        ];

        return in_array(
            $this->department?->nama_department,
            $fatNames,
            true
        );
    }

    public function markAsPaid(): void
    {
        $this->update([
            'status' => 'Paid',
            'paid_at' => now(),
            'paid_by' => auth()->id(),
        ]);
    }

    public function canBeMarkedAsPaid(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        // Cek apakah user department-nya FAT
        $userDepartment = $user->departments->first()?->nama_department;

        $fatNames = [
            'FAT',
            'Finance, Accounting dan Tax',
            'Finance, Accounting & Tax',
            'Finance Accounting Tax',
            'FAT Department',
        ];

        $userIsFat = in_array($userDepartment, $fatNames, true);

        // Bisa mark PAID kalau:
        // 1. Sudah Approved
        // 2. Belum PAID
        // 3. User department-nya FAT (siapapun rolenya)
        // 4. SPB ini memang untuk department FAT
        return $this->isApproved()
            && ! $this->isPaid()
            && $userIsFat;

    }
}
