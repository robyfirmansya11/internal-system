<?php

namespace App\Filament\Resources\Kasbons\Pages;

use App\Filament\Resources\Kasbons\KasbonResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListKasbons extends ListRecords
{
    protected static string $resource = KasbonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
