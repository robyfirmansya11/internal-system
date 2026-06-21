<?php

namespace App\Filament\Resources\Kasbons\Pages;

use App\Filament\Resources\Kasbons\KasbonResource;
use Filament\Resources\Pages\CreateRecord;

use App\Enums\Role;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class CreateKasbon extends CreateRecord
{
    protected static string $resource = KasbonResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();

        $department = $user->departments->first();

        if (! $department) {
            abort(403, 'User belum memiliki department');
        }

        return array_merge($data, [
            'user_id'       => $user->id,
            'department_id' => $department->id,
            'status'        => 'pending',
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $record = $this->record;

        $managers = User::where('level', Role::Superuser)
            ->whereHas('departments', function ($q) use ($record) {
                $q->where('departments.id', $record->department_id);
            })
            ->get();

        foreach ($managers as $manager) {
            Notification::make()
                ->title('Pengajuan Kasbon Baru')
                ->body("{$record->user->name} mengajukan kasbon sebesar Rp " . number_format($record->jumlah, 0, ',', '.'))
                ->icon('heroicon-o-banknotes')
                ->sendToDatabase($manager);
        }
    }
}
