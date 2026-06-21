<?php

namespace App\Models;

use App\Traits\HasApprovalWorkflow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lembur extends Model
{
    use HasApprovalWorkflow, SoftDeletes;

    const APPROVAL_LEVELS = 2;

    const LEVEL2_JABATAN = 'HRD';

    protected $fillable = [
        'user_id',
        'department_id',
        'bulan_lembur',
        'tanggal_lembur',
        'mulai_kerja',
        'selesai_kerja',
        'mulai_lembur',
        'selesai_lembur',
        'uang_makan',
        'uraian_pekerjaan',
        'jumlah_jam_lembur',
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
        'tanggal_lembur' => 'date',
        'approved_at' => 'datetime',
        'approved_manager_at' => 'datetime',
        'mulai_kerja' => 'datetime:H:i',
        'selesai_kerja' => 'datetime:H:i',
        'mulai_lembur' => 'datetime:H:i',
        'selesai_lembur' => 'datetime:H:i',
        'jumlah_jam_lembur' => 'decimal:2',
        'uang_makan' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | BOOT
    |--------------------------------------------------------------------------
    */

    protected static function booted(): void
    {
        static::creating(function ($lembur) {
            if (! $lembur->user_id) {
                $lembur->user_id = auth()->id();
            }

            if (! $lembur->department_id) {
                $lembur->department_id = auth()->user()
                    ?->departments()
                    ?->value('departments.id');
            }
        });
    }

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
