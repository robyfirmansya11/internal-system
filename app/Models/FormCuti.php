<?php

namespace App\Models;

use App\Traits\HasApprovalWorkflow;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FormCuti extends Model
{
    use HasApprovalWorkflow;

    protected $table = 'form_cuti';

    const APPROVAL_LEVELS = 3;

    const LEVEL2_JABATAN = 'HRD';

    const FINANCE_MANAGER_JABATAN = 'Finance Manager';

    private const APPROVAL_LEVEL_FINANCE_MANAGER = 2;

    private const APPROVAL_LEVEL_HRD = 3;

    private const APPROVAL_LEVEL_APPROVED = 4;

    protected $fillable = [
        'user_id',
        'department_id',
        'tahun',
        'tanggal_mulai',
        'tanggal_selesai',
        'jumlah_hari',
        'jenis_cuti',
        'alasan',
        'approval_level',
        'status',
        'approved_by',
        'approved_at',
        'approved_by_manager',
        'approved_manager_at',
        'approved_by_finance_manager',
        'approved_finance_manager_at',
        'approved_by_hrd',
        'approved_hrd_at',
        'rejected_by',
        'rejected_at',
        'rejected_note',
        'expired_at',
        'lampiran',
        'cancelled_by',
        'cancelled_at',

    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'approved_at' => 'datetime',
        'approved_manager_at' => 'datetime',
        'approved_finance_manager_at' => 'datetime',
        'approved_hrd_at' => 'datetime',
        'rejected_at' => 'datetime',
        'expired_at' => 'date',
        'cancelled_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | BOOT
    |--------------------------------------------------------------------------
    */

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if ($model->tahun) {
                $model->expired_at = Carbon::create($model->tahun + 1, 1, 31);
            }
            // validateJenisCuti & validateOverlap dihapus dari sini
            // sudah di-handle di mutateFormDataBeforeCreate
        });

        // updating juga tidak perlu validasi dari model
        // karena EditFormCuti akan handle sendiri nanti
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'approved_by_manager');
    }

    public function hrd()
    {
        return $this->belongsTo(User::class, 'approved_by_hrd');
    }

    public function financeManager()
    {
        return $this->belongsTo(User::class, 'approved_by_finance_manager');
    }

    public function rejector()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function kuota()
    {
        return $this->hasOne(KuotaCuti::class, 'user_id', 'user_id')
            ->where('tahun', $this->tahun);
    }

    /*
    |--------------------------------------------------------------------------
    | OVERRIDE TRAIT — approveByAdmin khusus untuk Cuti
    | Karena perlu potong kuota saat HRD approve
    |--------------------------------------------------------------------------
    */

    public function approveByAdmin(User $approver): bool
    {
        if ($this->approval_level !== self::APPROVAL_LEVEL_HRD) {
            return false;
        }

        if (! $this->isLevel2Approver($approver)) {
            return false;
        }

        DB::transaction(function () use ($approver) {
            // Potong kuota dulu
            $this->potongKuota();

            $this->update([
                'approved_by_hrd' => $approver->id,
                'approved_hrd_at' => now(),
                'approved_by' => $approver->id,
                'approved_at' => now(),
                'approval_level' => self::APPROVAL_LEVEL_APPROVED,
                'status' => 'Approved',
            ]);
        });

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | OVERRIDE TRAIT — approveByAtasan khusus untuk Cuti
    | Simpan ke approved_by_manager bukan hanya approved_by_manager dari trait
    |--------------------------------------------------------------------------
    */

    public function approveByAtasan(User $approver): bool
    {
        if ($this->approval_level !== 1) {
            return false;
        }

        if (! $this->isValidAtasan($approver)) {
            return false;
        }

        $updateData = [
            'approved_by_manager' => $approver->id,
            'approved_manager_at' => now(),
        ];

        // Cuti HRD selalu diajukan ke atasannya. Approval atasan adalah
        // approval final agar HRD tidak perlu (atau tidak bisa) approve sendiri.
        if ($this->user?->jabatan === self::LEVEL2_JABATAN) {
            DB::transaction(function () use ($approver, $updateData) {
                $this->potongKuota();
                $this->update(array_merge($updateData, [
                    'approved_by' => $approver->id,
                    'approved_at' => now(),
                    'approval_level' => self::APPROVAL_LEVEL_APPROVED,
                    'status' => 'Approved',
                ]));
            });

            return true;
        }

        // Kalau approver sekaligus HRD — auto approved
        if ($this->isLevel2Approver($approver)) {
            DB::transaction(function () use ($approver, $updateData) {
                $this->potongKuota();
                $this->update(array_merge($updateData, [
                    'approved_by_hrd' => $approver->id,
                    'approved_hrd_at' => now(),
                    'approved_by' => $approver->id,
                    'approved_at' => now(),
                    'approval_level' => self::APPROVAL_LEVEL_APPROVED,
                    'status' => 'Approved',
                ]));
            });

            return true;
        }

        // Staff setelah atasan langsung masuk ke tahap HRD.
        $this->update(array_merge($updateData, [
            'status' => 'Pending Approval',
            'approval_level' => self::APPROVAL_LEVEL_HRD,
        ]));

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    public function isQuotaCut(): bool
    {
        return in_array($this->jenis_cuti, [
            'Cuti Tahunan',
            'Cuti Haid',
        ], true);
    }

    /**
     * Tahap awal pengajuan cuti sesuai jabatan pemohon.
     */
    public static function initialApprovalLevel(User $user): int
    {
        if ($user->jabatan === 'Manager') {
            return self::APPROVAL_LEVEL_FINANCE_MANAGER;
        }

        if ($user->jabatan === self::FINANCE_MANAGER_JABATAN) {
            return self::APPROVAL_LEVEL_HRD;
        }

        $atasan = $user->profile?->atasan;

        // Staff tanpa atasan tidak boleh mandek; langsung ke HRD.
        return ! $atasan || $atasan->id === $user->id
            ? self::APPROVAL_LEVEL_HRD
            : 1;
    }

    public function approveByFinanceManager(User $approver): bool
    {
        if ($this->approval_level !== self::APPROVAL_LEVEL_FINANCE_MANAGER) {
            return false;
        }

        if (! $this->isFinanceManagerApprover($approver)) {
            return false;
        }

        $this->update([
            'approved_by_finance_manager' => $approver->id,
            'approved_finance_manager_at' => now(),
            'status' => 'Pending Approval',
            'approval_level' => self::APPROVAL_LEVEL_HRD,
        ]);

        return true;
    }

    public function isFinanceManagerApprover(User $approver): bool
    {
        return $approver->jabatan === self::FINANCE_MANAGER_JABATAN;
    }

    public function isWaitingFinanceManager(): bool
    {
        return (int) $this->approval_level === self::APPROVAL_LEVEL_FINANCE_MANAGER
            && $this->isPending();
    }

    public function isWaitingAdmin(): bool
    {
        return (int) $this->approval_level === self::APPROVAL_LEVEL_HRD
            && $this->isPending();
    }

    public function canBeApprovedBy(User $approver): bool
    {
        if ($this->isRejected() || $this->isApproved() || $this->isCancelled()) {
            return false;
        }

        return match ((int) $this->approval_level) {
            1 => $this->isValidAtasan($approver),
            self::APPROVAL_LEVEL_FINANCE_MANAGER => $this->isFinanceManagerApprover($approver),
            self::APPROVAL_LEVEL_HRD => $this->isLevel2Approver($approver),
            default => false,
        };
    }

    public function scopeWaitingApprovalFrom(Builder $query, User $approver): Builder
    {
        if ($approver->isSuperadmin()) {
            return $query->where('status', 'Pending Approval');
        }

        return $query
            ->where('status', 'Pending Approval')
            ->where(function (Builder $query) use ($approver): void {
                $query->where(function (Builder $query) use ($approver): void {
                    $query->where('approval_level', 1)
                        ->whereHas('user.profile', function (Builder $query) use ($approver): void {
                            $query->where('atasan_id', $approver->id);
                        });
                });

                if ($this->isFinanceManagerApprover($approver)) {
                    $query->orWhere('approval_level', self::APPROVAL_LEVEL_FINANCE_MANAGER);
                }

                if ($this->isLevel2Approver($approver)) {
                    $query->orWhere('approval_level', self::APPROVAL_LEVEL_HRD);
                }
            });
    }

    public function potongKuota(): void
    {
        if (! $this->isQuotaCut()) {
            return;
        }

        $kuota = KuotaCuti::where('user_id', $this->user_id)
            ->where('tahun', $this->tahun)
            ->lockForUpdate()
            ->first();

        if (! $kuota) {
            throw ValidationException::withMessages([
                'tahun' => 'Kuota cuti untuk tahun ini tidak ditemukan.',
            ]);
        }

        if ($kuota->sisa_cuti < $this->jumlah_hari) {
            throw ValidationException::withMessages([
                'jumlah_hari' => "Sisa kuota {$kuota->sisa_cuti} hari, dibutuhkan {$this->jumlah_hari} hari.",
            ]);
        }

        $kuota->update([
            'cuti_terpakai' => $kuota->cuti_terpakai + $this->jumlah_hari,

            'sisa_cuti' => $kuota->kuota_tahunan -
                ($kuota->cuti_terpakai + $this->jumlah_hari),
        ]);
    }

    public function getSisaKuotaAttribute(): ?int
    {
        return $this->kuota?->sisa_cuti;
    }

    public function isExpired(): bool
    {
        return $this->expired_at && $this->expired_at->isPast();
    }

    public function validateJenisCuti(): void
    {
        match ($this->jenis_cuti) {
            'Cuti Haid' => throw_if(
                $this->jumlah_hari > 2,
                ValidationException::withMessages([
                    'jumlah_hari' => 'Cuti Haid maksimal 2 hari.',
                ])
            ),
            'Cuti Khusus' => throw_if(
                $this->jumlah_hari < 1 || $this->jumlah_hari > 3,
                ValidationException::withMessages([
                    'jumlah_hari' => 'Cuti Khusus hanya 1–3 hari.',
                ])
            ),
            'Cuti Melahirkan' => throw_if(
                $this->jumlah_hari > 90,
                ValidationException::withMessages([
                    'jumlah_hari' => 'Cuti Melahirkan maksimal 3 bulan (90 hari).',
                ])
            ),
            'Cuti Keguguran' => throw_if(
                $this->jumlah_hari > 45,
                ValidationException::withMessages([
                    'jumlah_hari' => 'Cuti Keguguran maksimal 1.5 bulan (45 hari).',
                ])
            ),
            default => null,
        };
    }

    public function validateOverlap(): void
    {
        $exists = self::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id ?? 0)
            ->whereNotIn('status', ['Rejected', 'Cancelled'])
            ->where(function ($query) {
                $query
                    ->whereBetween('tanggal_mulai', [
                        $this->tanggal_mulai,
                        $this->tanggal_selesai,
                    ])
                    ->orWhereBetween('tanggal_selesai', [
                        $this->tanggal_mulai,
                        $this->tanggal_selesai,
                    ])
                    ->orWhere(function ($q) {
                        $q->where('tanggal_mulai', '<=', $this->tanggal_mulai)
                            ->where('tanggal_selesai', '>=', $this->tanggal_selesai);
                    });
            })
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'tanggal_mulai' => 'Tanggal cuti bertabrakan dengan pengajuan cuti lain yang sudah ada.',
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PERMISSION HELPERS
    |--------------------------------------------------------------------------
    */

    public function canBeEditedBy(User $user): bool
    {
        return $this->user_id === $user->id
            && $this->isPending();
    }

    public function canBeDeletedBy(User $user): bool
    {
        return $this->user_id === $user->id
            && $this->isPending();
    }

    /**
     * Pemohon hanya dapat membatalkan cuti sebelum pengajuan disetujui,
     * ditolak, atau telah dibatalkan.
     */
    public function canBeCancelledBy(User $user): bool
    {
        return $this->user_id === $user->id
            && ! $this->isApproved()
            && ! $this->isRejected()
            && ! $this->isCancelled();
    }

    /**
     * Terapkan aturan pembatalan yang sama di level model.
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
