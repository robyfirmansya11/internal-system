<?php

namespace App\Filament\Resources\PermohonanStempels\Pages;

use App\Filament\Resources\PermohonanStempels\PermohonanStempelResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPermohonanStempel extends EditRecord
{
    protected static string $resource = PermohonanStempelResource::class;

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
