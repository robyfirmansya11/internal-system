<?php

namespace App\Filament\Resources\FormCutis\Pages;

use App\Filament\Resources\FormCutis\FormCutiResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFormCutis extends ListRecords
{
    protected static string $resource = FormCutiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
