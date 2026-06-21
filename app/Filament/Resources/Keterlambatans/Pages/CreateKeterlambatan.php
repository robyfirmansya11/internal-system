<?php

namespace App\Filament\Resources\Keterlambatans\Pages;

use App\Enums\Role;
use App\Filament\Resources\Keterlambatans\KeterlambatanResource;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateKeterlambatan extends CreateRecord
{
    protected static string $resource = KeterlambatanResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();
        $department = $user->departments->first();

        if (! $department) {
            abort(403, 'User belum memiliki department.');
        }

        return array_merge($data, [
            'user_id' => $user->id,
            'department_id' => $department->id,
            // Status & approval_level di-handle oleh submitForApproval()
            // bootHasApprovalWorkflow set default 'Submitted' & 0
        ]);
    }

    protected function afterCreate(): void
    {
        $record = $this->record;
        $user = Auth::user();

        // Panggil submitForApproval — ini yang mengubah status & approval_level
        $record->submitForApproval($user);

        // Refresh record setelah update
        $record->refresh();

        // Kirim notifikasi berdasarkan hasil submitForApproval
        if ($record->isWaitingAtasan()) {
            // Normal flow — notif ke atasan
            $atasan = $user->atasan;

            if ($atasan) {
                Notification::make()
                    ->title('Pengajuan Keterlambatan Baru')
                    ->body("{$user->name} mengajukan izin keterlambatan.")
                    ->icon('heroicon-o-clock')
                    ->sendToDatabase($atasan);
            }

        } elseif ($record->isWaitingAdmin()) {
            // Auto-approved level 1 (atasan = diri sendiri)
            // Langsung notif ke HRD
            $hrds = User::where('level', Role::Admin)
                ->where('jabatan', 'HRD')
                ->get();

            foreach ($hrds as $hrd) {
                Notification::make()
                    ->title('Pengajuan Keterlambatan Baru')
                    ->body("{$user->name} mengajukan izin keterlambatan.")
                    ->icon('heroicon-o-clock')
                    ->sendToDatabase($hrd);
            }

        } elseif ($record->isApproved()) {
            // Auto-approved semua level (Summer case)
            Notification::make()
                ->title('Pengajuan Disetujui')
                ->body('Pengajuan keterlambatan Anda telah disetujui otomatis.')
                ->success()
                ->sendToDatabase($user);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
