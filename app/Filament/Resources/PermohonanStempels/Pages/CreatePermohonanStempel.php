<?php

namespace App\Filament\Resources\PermohonanStempels\Pages;

use App\Filament\Resources\PermohonanStempels\PermohonanStempelResource;
use App\Models\PermohonanStempel;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreatePermohonanStempel extends CreateRecord
{
    protected static string $resource = PermohonanStempelResource::class;

    /**
     * Approval rules:
     * 1. Finance Manager submits → ALWAYS auto-approved in full.
     * 2. Manager (Superuser, not FM) submits → ALWAYS skips the
     *    direct supervisor step, waits for Finance Manager approval.
     * 3. Regular staff → normal 1-level flow (supervisor approval
     *    = done), or auto-approved if no supervisor is assigned.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();
        $department = $user->departments->first();

        if (! $department) {
            Notification::make()
                ->title('Request Failed')
                ->body('Your account is not assigned to any department.')
                ->danger()
                ->send();

            $this->halt();
        }

        $isFinanceManager = $user->jabatan === PermohonanStempel::LEVEL2_JABATAN;
        $isManager = $user->jabatan === 'Manager';

        /*
        |--------------------------------------------------------------------------
        | RULE 1 — Manager & Finance Manager
        | Full approval otomatis
        |--------------------------------------------------------------------------
        */

        if ($isManager || $isFinanceManager) {
            return array_merge($data, [
                'user_id' => $user->id,
                'department_id' => $department->id,

                'status' => 'Approved',
                'approval_level' => 3,

                'approved_by_manager' => $user->id,
                'approved_manager_at' => now(),

                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | RULE 2 — Regular Employee
        | WAJIB menunggu approval atasan.
        |--------------------------------------------------------------------------
        */

        $atasan = $user->profile?->atasan;

        /*
        |--------------------------------------------------------------------------
        | Karyawan tidak punya atasan
        |--------------------------------------------------------------------------
        |
        | Jangan auto approve.
        | Lebih baik submission dihentikan karena tidak ada siapa yang
        | dapat melakukan approval.
        |
        */

        if (! $atasan) {
            Notification::make()
                ->title('Request Failed')
                ->body('Your account does not have a direct manager assigned. Please contact HR or IT.')
                ->danger()
                ->send();

            $this->halt();
        }

        /*
        |--------------------------------------------------------------------------
        | Regular Employee → Pending Manager Approval
        |--------------------------------------------------------------------------
        */

        return array_merge($data, [
            'user_id' => $user->id,
            'department_id' => $department->id,

            'status' => 'Pending Approval',
            'approval_level' => 1,
        ]);
    }

    protected function afterCreate(): void
    {
        $record = $this->record;
        $user = Auth::user();

        if ($record->isApproved()) {
            return;
        }

        if ($record->isWaitingAtasan()) {
            $atasan = $user->profile?->atasan;

            if ($atasan) {
                Notification::make()
                    ->title('New Stamp Application Letter')
                    ->body("{$user->name} submitted a stamp application request.")
                    ->icon('heroicon-o-document-text')
                    ->sendToDatabase($atasan);
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
