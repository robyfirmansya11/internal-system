<?php

namespace App\Filament\Resources\MealClaims\Pages;

use App\Filament\Resources\MealClaims\MealClaimResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMealClaim extends EditRecord
{
    protected static string $resource = MealClaimResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
