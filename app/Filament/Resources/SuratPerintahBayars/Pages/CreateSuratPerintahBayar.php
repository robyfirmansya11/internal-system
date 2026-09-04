<?php

namespace App\Filament\Resources\SuratPerintahBayars\Pages;

use App\Filament\Resources\SuratPerintahBayars\SuratPerintahBayarResource;
use App\Models\SuratPerintahBayar;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateSuratPerintahBayar extends CreateRecord
{
    protected static string $resource = SuratPerintahBayarResource::class;

    /**
     * Set user_id, department_id, dan inisiasi approval flow.
     *
     * Aturan approval:
     * 1. Finance Manager mengajukan → SELALU auto-approve penuh,
     *    tidak peduli dia punya atasan terdaftar atau tidak.
     * 2. Manager (Superuser, bukan FM) mengajukan → SELALU langsung
     *    ke Finance Manager (skip level atasan), karena Manager
     *    tidak punya "atasan" lain yang relevan untuk approval SPB.
     * 3. Staff biasa mengajukan → ikuti alur normal (atasan dulu,
     *    baru Finance Manager), kecuali tidak punya atasan terdaftar
     *    sama sekali (fallback langsung ke Finance Manager).
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

        $isFinanceManager = $user->jabatan === SuratPerintahBayar::LEVEL2_JABATAN;
        $isManager = $user->isSuperuser();

        /*
        |----------------------------------------------------------------------
        | ATURAN 1 — Finance Manager mengajukan sendiri
        | Selalu full-approve, TIDAK bergantung pada atasan_id.
        |----------------------------------------------------------------------
        */
        if ($isFinanceManager) {
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
        |----------------------------------------------------------------------
        | ATURAN 2 — Manager (Superuser, bukan FM) mengajukan sendiri
        | Selalu skip level atasan, langsung tunggu Finance Manager.
        |----------------------------------------------------------------------
        */
        if ($isManager) {
            return array_merge($data, [
                'user_id' => $user->id,
                'department_id' => $department->id,
                'status' => 'Pending Approval',
                'approval_level' => 2,
            ]);
        }

        /*
        |----------------------------------------------------------------------
        | ATURAN 3 — Staff biasa: cek atasan terdaftar
        |----------------------------------------------------------------------
        */
        $atasan = $user->profile?->atasan;
        $selfApprove = ! $atasan || $atasan->id === $user->id;

        if ($selfApprove) {
            // Tidak punya atasan terdaftar → fallback langsung ke Finance Manager
            return array_merge($data, [
                'user_id' => $user->id,
                'department_id' => $department->id,
                'status' => 'Pending Approval',
                'approval_level' => 2,
                'approved_by_manager' => $user->id,
                'approved_manager_at' => now(),
            ]);
        }

        // Normal: punya atasan → tunggu atasan approve dulu
        return array_merge($data, [
            'user_id' => $user->id,
            'department_id' => $department->id,
            'status' => 'Pending Approval',
            'approval_level' => 1,
        ]);
    }

    /**
     * Kirim notifikasi ke approver berikutnya setelah SPB dibuat.
     */
    protected function afterCreate(): void
    {
        $record = $this->record;
        $user = Auth::user();

        $jumlahFormatted = 'Rp '.number_format($record->jumlah_total, 0, ',', '.');

        if ($record->isApproved()) {
            // Finance Manager mengajukan sendiri → langsung approved
            Notification::make()
                ->title('Automatically Approved')
                ->success()
                ->sendToDatabase($user);

        } elseif ($record->isWaitingAtasan()) {
            // Staff dengan atasan terdaftar → notif ke atasan
            $atasan = $user->profile?->atasan;

            if ($atasan) {
                Notification::make()
                    ->title('New Payment Application Letter')
                    ->body("{$user->name} submitted a Payment Application Letter for {$jumlahFormatted}.")
                    ->icon('heroicon-o-document-text')
                    ->sendToDatabase($atasan);
            }

        } elseif ($record->isWaitingAdmin()) {
            // Manager mengajukan sendiri, ATAU staff tanpa atasan → langsung ke FM
            $fms = User::where('jabatan', SuratPerintahBayar::LEVEL2_JABATAN)->get();

            foreach ($fms as $fm) {
                Notification::make()
                    ->title('New Payment Application Letter')
                    ->body("{$user->name} submitted a Payment Application Letter for {$jumlahFormatted}.")
                    ->icon('heroicon-o-document-text')
                    ->sendToDatabase($fm);
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
