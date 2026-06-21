<?php

namespace App\Traits;

use App\Models\User;

trait HasApprovalWorkflow
{
    /*
    |--------------------------------------------------------------------------
    | BOOT — set default saat record dibuat
    |--------------------------------------------------------------------------
    */

    public static function bootHasApprovalWorkflow(): void
    {
        static::creating(function ($model) {
            $model->status ??= 'Submitted';
            $model->approval_level ??= 0;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | ACTIONS
    |--------------------------------------------------------------------------
    */

    public function submitForApproval(User $submitter): bool
    {
        if ($this->status !== 'Submitted') {
            return false;
        }

        $atasan = $submitter->profile?->atasan;
        $maxLevel = static::APPROVAL_LEVELS ?? 2;

        // Tidak punya atasan ATAU atasan adalah diri sendiri
        $selfApproveLevel1 = ! $atasan
            || $atasan->id === $submitter->id;

        if ($selfApproveLevel1) {
            $updateData = [
                'approved_by_manager' => $submitter->id,
                'approved_manager_at' => now(),
            ];

            // PermohonanStempel (max level 1) — langsung approved
            if ($maxLevel === 1) {
                $this->update(array_merge($updateData, [
                    'status' => 'Approved',
                    'approval_level' => 2,
                    'approved_by' => $submitter->id,
                    'approved_at' => now(),
                ]));

                return true;
            }

            // Auto-approve level 1, lanjut tunggu level 2
            $this->update(array_merge($updateData, [
                'status' => 'Pending Approval',
                'approval_level' => 2,
            ]));

            return true;
        }

        // Normal flow — tunggu atasan
        $this->update([
            'status' => 'Pending Approval',
            'approval_level' => 1,
        ]);

        return true;
    }

    public function approveByAtasan(User $approver): bool
    {
        if ($this->approval_level !== 1) {
            return false;
        }

        if (! $this->isValidAtasan($approver)) {
            return false;
        }

        $maxLevel = static::APPROVAL_LEVELS ?? 2;

        $updateData = [
            'approved_by_manager' => $approver->id,
            'approved_manager_at' => now(),
        ];

        /*
        |--------------------------------------------------------------------------
        | Jika approval hanya 1 level
        |--------------------------------------------------------------------------
        */
        if ($maxLevel === 1) {

            $this->update(array_merge($updateData, [
                'status' => 'Approved',
                'approval_level' => 2,
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ]));

            return true;
        }

        /*
        |--------------------------------------------------------------------------
        | Jika Atasan juga merupakan Level 2 Approver
        | Contoh:
        | Staff
        |    ↓
        | Finance Manager
        |--------------------------------------------------------------------------
        */
        if ($this->isLevel2Approver($approver)) {

            $this->update(array_merge($updateData, [
                'status' => 'Approved',
                'approval_level' => 3,
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ]));

            return true;
        }

        /*
        |--------------------------------------------------------------------------
        | Normal Flow
        |--------------------------------------------------------------------------
        */
        $this->update(array_merge($updateData, [
            'status' => 'Pending Approval',
            'approval_level' => 2,
        ]));

        return true;
    }

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

    public function reject(User $rejector, string $note): bool
    {
        if (! $this->isPending()) {
            return false;
        }

        if (! $this->canBeApprovedBy($rejector)) {
            return false;
        }

        $this->update([
            'status' => 'Rejected',
            'approval_level' => -1, // ← eksplisit, tidak bisa di-approve siapapun
            'rejected_by' => $rejector->id,
            'rejected_note' => $note,
        ]);

        return true;
    }
    /*
    |--------------------------------------------------------------------------
    | VALIDATORS
    |--------------------------------------------------------------------------
    */

    public function isValidAtasan(User $approver): bool
    {
        return $this->user->profile?->atasan_id === $approver->id;
    }

    public function isLevel2Approver(User $approver): bool
    {
        $jabatanRequired = static::LEVEL2_JABATAN ?? null;

        if (! $jabatanRequired) {
            return false;
        }

        // Cek jabatan saja, tidak terikat ke level tertentu
        // Ini memungkinkan Finance Manager = Superuser (Summer)
        // atau HRD = Admin
        return $approver->jabatan === $jabatanRequired;
    }

    public function canBeApprovedBy(User $approver): bool
    {
        // Kalau sudah rejected, tidak bisa di-approve siapapun
        if ($this->isRejected() || $this->isApproved()) {
            return false;
        }

        return match ((int) $this->approval_level) {
            1 => $this->isValidAtasan($approver),
            2 => $this->isLevel2Approver($approver),
            default => false,
        };
    }

    /*
    |--------------------------------------------------------------------------
    | STATUS HELPERS
    |--------------------------------------------------------------------------
    */

    public function isSubmitted(): bool
    {
        return $this->status === 'Submitted';
    }

    public function isPending(): bool
    {
        return $this->status === 'Pending Approval';
    }

    public function isApproved(): bool
    {
        return $this->status === 'Approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'Rejected'
            || (int) $this->approval_level === -1;
    }

    public function isWaitingAtasan(): bool
    {
        return (int) $this->approval_level === 1 && $this->isPending();
    }

    public function isWaitingAdmin(): bool
    {
        return (int) $this->approval_level === 2 && $this->isPending();
    }

    public function isLocked(): bool
    {
        return ! $this->isPending();
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeWaitingApprovalFrom($query, User $approver)
    {
        // Superadmin bisa lihat semua
        if ($approver->isSuperadmin()) {
            return $query->where('status', 'Pending Approval');
        }

        $jabatanRequired = static::LEVEL2_JABATAN ?? null;
        $isLevel2Approver = $jabatanRequired
            && $approver->jabatan === $jabatanRequired;

        // Superuser yang JUGA Level 2 Approver (contoh: Summer = Finance Manager)
        // Bisa lihat SEMUA pengajuan level 1 (bawahan) DAN level 2
        if ($approver->isSuperuser() && $isLevel2Approver) {
            return $query
                ->where('status', 'Pending Approval')
                ->where(function ($q) use ($approver) {
                    $q
                        // Level 1: bawahan langsung
                        ->where(function ($q2) use ($approver) {
                            $q2->where('approval_level', 1)
                                ->whereHas('user.profile', function ($q3) use ($approver) {
                                    $q3->where('atasan_id', $approver->id);
                                });
                        })
                        // Level 2: semua yang menunggu approval FM
                        ->orWhere('approval_level', 2);
                });
        }

        // Superuser biasa — hanya lihat bawahan langsung di level 1
        if ($approver->isSuperuser()) {
            return $query
                ->where('status', 'Pending Approval')
                ->where('approval_level', 1)
                ->whereHas('user.profile', function ($q) use ($approver) {
                    $q->where('atasan_id', $approver->id);
                });
        }

        // Admin yang jabatannya Level 2 Approver (HRD, Finance Manager)
        if ($approver->isAdmin() && $isLevel2Approver) {
            return $query
                ->where('status', 'Pending Approval')
                ->where('approval_level', 2);
        }

        return $query->whereRaw('1 = 0');
    }

    /*
    |--------------------------------------------------------------------------
    | PERMISSION HELPERS
    |--------------------------------------------------------------------------
    */

    public function canBeEditedBy(User $user): bool
    {
        return $this->user_id === $user->id
            && ($this->isSubmitted() || $this->isPending()); // tambah isPending()
    }

    public function canBeDeletedBy(User $user): bool
    {
        return $this->user_id === $user->id
            && ! $this->isApproved(); // semua kecuali Approved
    }
}
