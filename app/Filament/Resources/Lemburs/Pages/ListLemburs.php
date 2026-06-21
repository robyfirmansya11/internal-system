<?php

namespace App\Filament\Resources\Lemburs\Pages;

use App\Filament\Resources\Lemburs\LemburResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLemburs extends ListRecords
{
    protected static string $resource = LemburResource::class;

    protected function getHeaderActions(): array
    {
        // Superuser tidak bisa buat pengajuan lembur
        if (auth()->user()->isSuperuser()) {
            return [];
        }

        return [
            CreateAction::make(),
        ];
    }
}
