<?php

namespace App\Filament\Resources\SuratPerintahBayars\Pages;

use App\Filament\Resources\SuratPerintahBayars\SuratPerintahBayarResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditSuratPerintahBayar extends EditRecord
{
    protected static string $resource = SuratPerintahBayarResource::class;

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
