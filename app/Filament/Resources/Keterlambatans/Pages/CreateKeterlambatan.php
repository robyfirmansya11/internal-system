<?php

namespace App\Filament\Resources\Keterlambatans\Pages;

use App\Filament\Resources\Keterlambatans\KeterlambatanResource;
use App\Models\Keterlambatan;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateKeterlambatan extends CreateRecord
{
    protected static string $resource = KeterlambatanResource::class;

    /**
     * Set user_id, department_id, dan inisiasi approval flow.
     *
     * Skenario:
     * 1. Normal      → user → atasan → HRD → Approved
     * 2. Atasan = HRD → user → atasan/HRD approve sekali → langsung Approved
     * 3. Tidak punya atasan → skip level 1, langsung tunggu HRD
     * 4. User sendiri = HRD → tetap ke atasan dulu (kalau punya atasan)
     *                         atau langsung Approved (kalau tidak punya atasan)
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

        // Skenario 4: User sendiri adalah HRD tapi punya atasan
        // → tetap harus approval dari atasan dulu
        if (! $selfApprove) {
            return array_merge($data, [
                'user_id' => $user->id,
                'department_id' => $department->id,
                'status' => 'Pending Approval',
                'approval_level' => 1,
            ]);
        }

        // Skenario 4 lanjut: HRD tidak punya atasan → langsung Approved
        if ($selfApprove && $user->jabatan === Keterlambatan::LEVEL2_JABATAN) {
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

        // Skenario 3: Tidak punya atasan, bukan HRD → skip ke level 2
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
     * Kirim notifikasi ke approver berikutnya.
     */
    protected function afterCreate(): void
    {
        $record = $this->record;
        $user = Auth::user();

        $record->refresh();

        if ($record->isApproved()) {
            // HRD tanpa atasan → auto approved
            Notification::make()
                ->title('Pengajuan Disetujui Otomatis')
                ->success()
                ->sendToDatabase($user);

        } elseif ($record->isWaitingAtasan()) {
            // Normal flow — notif ke atasan
            $atasan = $user->profile?->atasan;

            if ($atasan) {
                Notification::make()
                    ->title('Pengajuan Keterlambatan Baru')
                    ->body("{$user->name} mengajukan izin keterlambatan.")
                    ->icon('heroicon-o-clock')
                    ->sendToDatabase($atasan);
            }

        } elseif ($record->isWaitingAdmin()) {
            // Tidak punya atasan → langsung ke HRD
            $hrds = User::where('jabatan', Keterlambatan::LEVEL2_JABATAN)->get();

            foreach ($hrds as $hrd) {
                Notification::make()
                    ->title('Pengajuan Keterlambatan Baru')
                    ->body("{$user->name} mengajukan izin keterlambatan.")
                    ->icon('heroicon-o-clock')
                    ->sendToDatabase($hrd);
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
