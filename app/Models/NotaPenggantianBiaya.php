<?php

namespace App\Models;

use App\Traits\HasApprovalWorkflow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotaPenggantianBiaya extends Model
{
    use HasApprovalWorkflow, SoftDeletes;

    protected $table = 'nota_penggantian_biaya';

    /** Atasan langsung → Finance Manager. */
    public const APPROVAL_LEVELS = 2;

    public const LEVEL2_JABATAN = 'Finance Manager';

    protected $fillable = [
        'company_id', 'user_id', 'department_id', 'tanggal', 'keterangan',
        'jumlah', 'jumlah_total', 'terbilang', 'informasi_transfer',
        'jumlah_lampiran', 'lampiran', 'status', 'approval_level',
        'approved_by_manager', 'approved_manager_at', 'approved_by', 'approved_at',
        'rejected_by', 'rejected_at', 'rejected_note', 'cancelled_by', 'cancelled_at',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'jumlah' => 'decimal:2',
        'jumlah_total' => 'decimal:2',
        'approved_manager_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function approverManager(): BelongsTo { return $this->belongsTo(User::class, 'approved_by_manager'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function rejector(): BelongsTo { return $this->belongsTo(User::class, 'rejected_by'); }
    public function cancelledBy(): BelongsTo { return $this->belongsTo(User::class, 'cancelled_by'); }
    public function details(): HasMany { return $this->hasMany(NotaPenggantianBiayaDetail::class); }

    public function recalculateTotal(): void
    {
        $total = $this->details()->sum('jumlah');

        $this->update([
            'jumlah' => $total,
            'jumlah_total' => $total,
            'terbilang' => \App\Filament\Resources\NotaPenggantianBiayas\Schemas\NotaPenggantianBiayaForm::terbilang((int) $total).' Rupiah',
        ]);
    }

    public function isLocked(): bool
    {
        return $this->isApproved() || $this->isRejected() || $this->isCancelled();
    }

    public function canBeEditedBy(User $user): bool
    {
        return $this->user_id === $user->id && ($this->isSubmitted() || $this->isPending());
    }

    public function canBeDeletedBy(User $user): bool
    {
        return $this->user_id === $user->id && ! $this->isApproved() && ! $this->isRejected();
    }

    public function canBeCancelledBy(User $user): bool
    {
        return $this->user_id === $user->id
            && ! $this->isApproved()
            && ! $this->isRejected()
            && ! $this->isCancelled();
    }

    public function cancel(User $user): bool
    {
        if (! $this->canBeCancelledBy($user)) {
            return false;
        }

        $this->update(['status' => 'Cancelled', 'cancelled_by' => $user->id, 'cancelled_at' => now()]);

        return true;
    }
}
