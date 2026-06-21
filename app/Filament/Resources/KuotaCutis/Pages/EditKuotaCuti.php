<?php

namespace App\Filament\Resources\KuotaCutis\Pages;

use App\Filament\Resources\KuotaCutis\KuotaCutiResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditKuotaCuti extends EditRecord
{
    protected static string $resource = KuotaCutiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
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
