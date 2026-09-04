<?php

namespace App\Filament\Resources\PerjalananDinas\Pages;

use App\Filament\Resources\PerjalananDinas\PerjalananDinasResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPerjalananDinas extends ListRecords
{
    protected static string $resource = PerjalananDinasResource::class;

    protected function getHeaderActions(): array
    {
        // Superuser tidak bisa buat pengajuan
        /*  if (auth()->user()->isSuperuser()) {
             return []; */
        return [
            CreateAction::make(),
        ];
        // semua user bisa buat pengajuan
    }
}
