<?php

namespace App\Filament\Resources\RegisterSurats\Pages;

use App\Filament\Resources\RegisterSurats\RegisterSuratResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateRegisterSurat extends CreateRecord
{
    protected static string $resource = RegisterSuratResource::class;

    /**
     * Redirect ke halaman list setelah berhasil create.
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Menambahkan data sistem secara otomatis
     * sebelum record disimpan.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();

        $department = $user->departments->first();

        if (! $department) {
            abort(403, 'Your account is not assigned to any department');
        }

        return array_merge($data, [
            'user_id' => $user->id,
            'department_id' => $department->id,
        ]);
    }
}
