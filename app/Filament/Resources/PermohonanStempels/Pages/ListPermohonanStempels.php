<?php

namespace App\Filament\Resources\PermohonanStempels\Pages;

use App\Filament\Resources\PermohonanStempels\PermohonanStempelResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPermohonanStempels extends ListRecords
{
    protected static string $resource = PermohonanStempelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
