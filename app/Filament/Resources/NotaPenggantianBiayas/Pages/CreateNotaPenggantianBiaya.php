<?php

namespace App\Filament\Resources\NotaPenggantianBiayas\Pages;

use App\Filament\Resources\NotaPenggantianBiayas\NotaPenggantianBiayaResource;
use App\Models\NotaPenggantianBiaya;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateNotaPenggantianBiaya extends CreateRecord
{
    protected static string $resource = NotaPenggantianBiayaResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();
        $department = $user->departments->first();

        if (! $department) {
            Notification::make()->title('Request Failed')->body('Your account is not assigned to any department.')->danger()->send();
            $this->halt();
        }

        // Kolom header lama tetap wajib di database, sedangkan nilai rincian
        // sekarang disimpan pada relasi details. Isi header ini agar insert
        // awal berhasil sebelum Filament menyimpan baris repeater.
        $base = [
            'user_id' => $user->id,
            'department_id' => $department->id,
            'keterangan' => $data['keterangan'] ?? '',
            'jumlah' => $data['jumlah_total'] ?? 0,
        ];

        if ($user->jabatan === NotaPenggantianBiaya::LEVEL2_JABATAN) {
            return array_merge($data, $base, [
                'status' => 'Approved', 'approval_level' => 3,
                'approved_by_manager' => $user->id, 'approved_manager_at' => now(),
                'approved_by' => $user->id, 'approved_at' => now(),
            ]);
        }

        if ($user->isSuperuser()) {
            return array_merge($data, $base, ['status' => 'Pending Approval', 'approval_level' => 2]);
        }

        $atasan = $user->profile?->atasan;

        return array_merge($data, $base, [
            'status' => 'Pending Approval',
            'approval_level' => (! $atasan || $atasan->id === $user->id) ? 2 : 1,
        ]);
    }

    protected function afterCreate(): void
    {
        $record = $this->record;
        $user = Auth::user();
        $amount = 'Rp '.number_format($record->jumlah_total, 0, ',', '.');

        if ($record->isApproved()) {
            Notification::make()->title('Automatically Approved')->success()->sendToDatabase($user);
        } elseif ($record->isWaitingAtasan() && $user->profile?->atasan) {
            Notification::make()
                ->title('New Expense Reimbursement Note')
                ->body("{$user->name} submitted an Expense Reimbursement Note for {$amount}.")
                ->sendToDatabase($user->profile->atasan);
        } elseif ($record->isWaitingAdmin()) {
            User::where('jabatan', NotaPenggantianBiaya::LEVEL2_JABATAN)->get()
                ->each(fn (User $financeManager) => Notification::make()
                    ->title('New Expense Reimbursement Note')
                    ->body("{$user->name} submitted an Expense Reimbursement Note for {$amount}.")
                    ->sendToDatabase($financeManager));
        }
    }

    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
}
