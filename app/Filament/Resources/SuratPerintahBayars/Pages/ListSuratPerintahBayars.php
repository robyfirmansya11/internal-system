<?php

namespace App\Filament\Resources\SuratPerintahBayars\Pages;

use App\Filament\Resources\SuratPerintahBayars\SuratPerintahBayarResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSuratPerintahBayars extends ListRecords
{
    protected static string $resource = SuratPerintahBayarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
