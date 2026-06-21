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
     * Skenario:
     * 1. Normal      → user → atasan → Finance Manager → Approved
     * 2. Atasan = FM → user → atasan/FM approve sekali → langsung Approved
     * 3. Tidak punya atasan → skip level 1, langsung tunggu FM
     * 4. User sendiri = FM → langsung Approved
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();
        $department = $user->departments->first();

        if (! $department) {
            Notification::make()
                ->title('Gagal')
                ->body('User belum memiliki department.')
                ->danger()
                ->send();

            $this->halt();
        }

        $atasan = $user->profile?->atasan;
        $selfApprove = ! $atasan || $atasan->id === $user->id;

        // Skenario 4: User sendiri adalah Finance Manager
        if ($selfApprove && $user->jabatan === SuratPerintahBayar::LEVEL2_JABATAN) {
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

        // Skenario 3: Tidak punya atasan, bukan FM → skip ke level 2
        if ($selfApprove) {
            return array_merge($data, [
                'user_id' => $user->id,
                'department_id' => $department->id,
                'status' => 'Pending Approval',
                'approval_level' => 2,
                'approved_by_manager' => $user->id,
                'approved_manager_at' => now(),
            ]);
        }

        // Skenario 1 & 2: punya atasan → tunggu atasan dulu
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
            // Skenario 4: FM mengajukan sendiri
            Notification::make()
                ->title('SPB Disetujui Otomatis')
                ->success()
                ->sendToDatabase($user);

        } elseif ($record->isWaitingAtasan()) {
            // Skenario 1 & 2: notif ke atasan
            $atasan = $user->profile?->atasan;

            if ($atasan) {
                Notification::make()
                    ->title('Pengajuan SPB Baru')
                    ->body("{$user->name} mengajukan SPB {$jumlahFormatted}.")
                    ->icon('heroicon-o-document-text')
                    ->sendToDatabase($atasan);
            }

        } elseif ($record->isWaitingAdmin()) {
            // Skenario 3: tidak punya atasan → langsung ke FM
            $fms = User::where('jabatan', SuratPerintahBayar::LEVEL2_JABATAN)->get();

            foreach ($fms as $fm) {
                Notification::make()
                    ->title('Pengajuan SPB Baru')
                    ->body("{$user->name} mengajukan SPB {$jumlahFormatted}.")
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
