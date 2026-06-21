<?php

namespace App\Filament\Resources\FormCutis\Pages;

use App\Filament\Resources\FormCutis\FormCutiResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFormCuti extends EditRecord
{
    protected static string $resource = FormCutiResource::class;

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
