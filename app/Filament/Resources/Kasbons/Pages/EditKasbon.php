<?php

namespace App\Filament\Resources\Kasbons\Pages;

use App\Filament\Resources\Kasbons\KasbonResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditKasbon extends EditRecord
{
    protected static string $resource = KasbonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn () => $this->record->canBeDeletedBy(auth()->user())),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
