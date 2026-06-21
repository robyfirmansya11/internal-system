<?php

namespace App\Filament\Resources\Lemburs\Pages;

use App\Enums\Role;
use App\Filament\Resources\Lemburs\LemburResource;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateLembur extends CreateRecord
{
    protected static string $resource = LemburResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        $department = $user->departments()->first();

        if (! $department) {
            Notification::make()
                ->title('Gagal')
                ->body('User tidak memiliki department.')
                ->danger()
                ->send();

            $this->halt();
        }

        $atasan = $user->profile?->atasan;
        $selfApprove = ! $atasan || $atasan->id === $user->id;

        return array_merge($data, [
            'user_id' => $user->id,
            'department_id' => $department->id,
            'status' => 'Pending Approval',
            'approval_level' => $selfApprove ? 2 : 1,
            'approved_by_manager' => $selfApprove ? $user->id : null,
            'approved_manager_at' => $selfApprove ? now() : null,
        ]);
    }

    protected function afterCreate(): void
    {
        $record = $this->record;
        $user = auth()->user();

        if ($record->isWaitingAtasan()) {
            $atasan = $user->atasan;

            if ($atasan) {
                Notification::make()
                    ->title('Pengajuan Lembur Baru')
                    ->body("{$user->name} mengajukan lembur.")
                    ->icon('heroicon-o-clock')
                    ->sendToDatabase($atasan);
            }

        } elseif ($record->isWaitingAdmin()) {
            $hrds = User::where('level', Role::Admin)
                ->where('jabatan', 'HRD')
                ->get();

            foreach ($hrds as $hrd) {
                Notification::make()
                    ->title('Pengajuan Lembur Baru')
                    ->body("{$user->name} mengajukan lembur.")
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
