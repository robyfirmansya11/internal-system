<?php

namespace App\Filament\Resources\RegisterSurats\Pages;

use App\Filament\Resources\RegisterSurats\RegisterSuratResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRegisterSurats extends ListRecords
{
    protected static string $resource = RegisterSuratResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
