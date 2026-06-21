<?php

namespace App\Models;

use App\Traits\HasApprovalWorkflow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PerjalananDinas extends Model
{
    use HasApprovalWorkflow, SoftDeletes;

    protected $table = 'perjalanan_dinas';

    /**
     * Approval: Atasan (level 1) → Finance Manager (level 2)
     */
    const APPROVAL_LEVELS = 2;

    const LEVEL2_JABATAN = 'Finance Manager';

    protected $fillable = [
        'user_id',
        'department_id',
        'company_id',
        'keterangan',
        'jumlah_lampiran',
        'total',
        'terbilang',
        'catatan',
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
        'approved_at' => 'datetime',
        'approved_manager_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'total' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

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

    /** Perusahaan terkait */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** Detail item perjalanan */
    public function details(): HasMany
    {
        return $this->hasMany(PerjalananDinasDetail::class);
    }

    /** User yang approve sebagai atasan */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /** User yang approve sebagai manager */
    public function approvedByManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_manager');
    }

    /** User yang reject */
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
     * Hitung ulang total dari semua detail dan simpan ke record.
     * Dipanggil setelah create/save detail.
     */
    public function recalculateTotal(): void
    {
        $total = $this->details()->sum('subtotal');

        $this->update([
            'total' => $total,
            'terbilang' => \App\Filament\Resources\PerjalananDinas\Schemas\PerjalananDinasForm::terbilang(
                (int) $total
            ).' Rupiah',
        ]);
    }

    /**
     * Apakah pengajuan bisa diedit oleh user ini.
     * Hanya bisa diedit kalau masih Submitted dan milik sendiri.
     */
    public function canBeEditedBy(User $user): bool
    {
        return $this->user_id === $user->id
            && $this->isSubmitted();
    }

    /**
     * Apakah pengajuan bisa dihapus oleh user ini.
     * Hanya bisa dihapus kalau belum Approved.
     */
    public function canBeDeletedBy(User $user): bool
    {
        return $this->user_id === $user->id
            && ! $this->isApproved();
    }
}
