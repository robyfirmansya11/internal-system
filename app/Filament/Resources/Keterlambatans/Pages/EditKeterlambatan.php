<?php

namespace App\Filament\Resources\Keterlambatans\Pages;

use App\Filament\Resources\Keterlambatans\KeterlambatanResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditKeterlambatan extends EditRecord
{
    protected static string $resource = KeterlambatanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
{
    return $this->getResource()::getUrl('index');
}

}
