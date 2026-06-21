<?php

namespace App\Filament\Resources\PermohonanStempels\Pages;

use App\Filament\Resources\PermohonanStempels\PermohonanStempelResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreatePermohonanStempel extends CreateRecord
{
    protected static string $resource = PermohonanStempelResource::class;

    /**
     * Set user_id, department_id, dan inisiasi approval flow.
     *
     * Permohonan Stempel hanya 1 level approval (ke atasan langsung).
     * Kalau tidak punya atasan → langsung Approved.
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

        if ($selfApprove) {
            // Tidak punya atasan → langsung Approved
            return array_merge($data, [
                'user_id' => $user->id,
                'department_id' => $department->id,
                'status' => 'Approved',
                'approval_level' => 2,
                'approved_by_manager' => $user->id,
                'approved_manager_at' => now(),
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);
        }

        return array_merge($data, [
            'user_id' => $user->id,
            'department_id' => $department->id,
            'status' => 'Pending Approval',
            'approval_level' => 1,
        ]);
    }

    /**
     * Kirim notifikasi ke atasan langsung setelah record dibuat.
     */
    protected function afterCreate(): void
    {
        $record = $this->record;
        $user = Auth::user();

        if ($record->isApproved()) {
            // Auto-approved (tidak punya atasan)
            return;
        }

        // Notif ke atasan langsung
        $atasan = $user->profile?->atasan;

        if ($atasan) {
            Notification::make()
                ->title('Permohonan Stempel Baru')
                ->body("{$user->name} mengajukan permohonan stempel.")
                ->icon('heroicon-o-document-text')
                ->sendToDatabase($atasan);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
