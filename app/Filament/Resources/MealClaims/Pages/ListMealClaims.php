<?php

namespace App\Filament\Resources\MealClaims\Pages;

use App\Filament\Resources\MealClaims\MealClaimResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMealClaims extends ListRecords
{
    protected static string $resource = MealClaimResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('New Meal Claim')];
    }
}
