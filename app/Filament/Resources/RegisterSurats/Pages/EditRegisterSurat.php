<?php

namespace App\Filament\Resources\RegisterSurats\Pages;

use App\Filament\Resources\RegisterSurats\RegisterSuratResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditRegisterSurat extends EditRecord
{
    protected static string $resource = RegisterSuratResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
            protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
