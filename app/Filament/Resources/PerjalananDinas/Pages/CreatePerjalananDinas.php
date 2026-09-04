<?php

namespace App\Filament\Resources\PerjalananDinas\Pages;

use App\Filament\Resources\PerjalananDinas\PerjalananDinasResource;
use App\Models\PerjalananDinas;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Arr;

class CreatePerjalananDinas extends CreateRecord
{
    protected static string $resource = PerjalananDinasResource::class;

    /**
     * Approval Flow:
     *
     * 1. Finance Manager mengajukan sendiri
     *    → Langsung Approved (full approve otomatis)
     *
     * 2. Manager (Superuser biasa, bukan FM) mengajukan
     *    → Skip level 1, langsung tunggu approval Finance Manager (level 2)
     *
     * 3. Staff punya atasan
     *    → Atasan (level 1) → Finance Manager (level 2) → Approved
     *
     * 4. Staff tidak punya atasan
     *    → Langsung ke Finance Manager (level 2)
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        $department = $user->departments->first();

        if (! $department) {
            Notification::make()
                ->title('Department Not Found')
                ->body('Your account is not assigned to any department. Please contact the HR or System Administrator.')
                ->danger()
                ->send();

            $this->halt();
        }

        /*
        |--------------------------------------------------------------------------
        | FINANCE MANAGER → AUTO FULL APPROVE
        |--------------------------------------------------------------------------
        */

        if ($user->jabatan === PerjalananDinas::LEVEL2_JABATAN) {
            return array_merge($data, [
                'user_id' => $user->id,
                'department_id' => $department->id,

                'status' => 'Approved',
                'approval_level' => 3,

                // Approval level 1 / manager
                'approved_by_manager' => $user->id,
                'approved_manager_at' => now(),

                // Approval final / Finance Manager
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | USER PUNYA ATASAN
        |--------------------------------------------------------------------------
        */

        $atasan = $user->profile?->atasan;

        if ($atasan && $atasan->id !== $user->id) {
            return array_merge($data, [
                'user_id' => $user->id,
                'department_id' => $department->id,

                'status' => 'Pending Approval',
                'approval_level' => 1,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | USER TIDAK PUNYA ATASAN
        |--------------------------------------------------------------------------
        |
        | Masuk ke HRD.
        |
        */

        return array_merge($data, [
            'user_id' => $user->id,
            'department_id' => $department->id,

            'status' => 'Pending Approval',
            'approval_level' => 2,
        ]);
    }

    /**
     * Setelah record dibuat:
     * - Hitung total
     * - Kirim notifikasi ke approver yang tepat
     */
    protected function afterCreate(): void
    {
        $record = $this->record;
        $user = auth()->user();

        // Filament normally persists a relationship repeater before this hook.
        // Keep a fallback here because a stale Livewire form state must not leave
        // a submitted travel request without its detail rows.
        $this->persistDetailsWhenMissing($record);

        // Hitung ulang total dari detail
        $record->recalculateTotal();
        $record->refresh();

        /*
        |--------------------------------------------------------------------------
        | FINANCE MANAGER AUTO APPROVED
        |--------------------------------------------------------------------------
        */

        if ($record->isApproved()) {
            Notification::make()
                ->title('Travel Reimbursement Approved')
                ->body('Your travel reimbursement has been automatically fully approved.')
                ->success()
                ->sendToDatabase($user);

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | MENUNGGU ATASAN
        |--------------------------------------------------------------------------
        */

        if ($record->isWaitingAtasan()) {
            $atasan = $user->profile?->atasan;

            if ($atasan) {
                Notification::make()
                    ->title('New Travel Reimbursement Request')
                    ->body("{$user->name} has submitted a travel reimbursement request.")
                    ->icon('heroicon-o-briefcase')
                    ->sendToDatabase($atasan);
            }

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | MENUNGGU HRD
        |--------------------------------------------------------------------------
        */

        if ($record->isWaitingAdmin()) {
            $hrdUsers = User::where('jabatan', 'HRD')->get();

            foreach ($hrdUsers as $hrd) {
                Notification::make()
                    ->title('New Travel Reimbursement Request')
                    ->body("{$user->name}'s travel reimbursement request requires HRD approval.")
                    ->icon('heroicon-o-briefcase')
                    ->sendToDatabase($hrd);
            }
        }
    }

    /**
     * Persist the repeater payload only when Filament did not save it itself.
     * This condition makes the fallback idempotent and avoids duplicate details.
     */
    private function persistDetailsWhenMissing(PerjalananDinas $record): void
    {
        if ($record->details()->exists()) {
            return;
        }

        $details = $this->form->getRawState()['details'] ?? [];

        foreach ($details as $detail) {
            if (! is_array($detail)) {
                continue;
            }

            $record->details()->create(Arr::only($detail, [
                'tanggal_berangkat',
                'waktu_berangkat',
                'tempat_berangkat',
                'tanggal_tujuan',
                'waktu_tujuan',
                'tempat_tujuan',
                'jumlah_hari',
                'amount_transportasi',
                'amount_tunjangan',
                'lama_hotel',
                'amount_hotel',
                'misc',
                'amount_other',
            ]));
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
