<?php

namespace App\Filament\Resources\PerjalananDinas\Pages;

use App\Filament\Resources\PerjalananDinas\PerjalananDinasResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditPerjalananDinas extends EditRecord
{
    protected static string $resource = PerjalananDinasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn () => $this->record->canBeDeletedBy(auth()->user())),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    /**
     * Setelah record di-save, hitung ulang total dari details.
     */
    protected function afterSave(): void
    {
        $this->record->recalculateTotal();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
