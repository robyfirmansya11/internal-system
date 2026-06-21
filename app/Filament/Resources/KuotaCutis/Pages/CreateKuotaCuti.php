<?php

namespace App\Filament\Resources\KuotaCutis\Pages;

use App\Filament\Resources\KuotaCutis\KuotaCutiResource;
use Filament\Resources\Pages\CreateRecord;

class CreateKuotaCuti extends CreateRecord
{
    protected static string $resource = KuotaCutiResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['cuti_terpakai'] ??= 0;

        $data['sisa_cuti'] =
            $data['kuota_tahunan'] -
            $data['cuti_terpakai'];

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
