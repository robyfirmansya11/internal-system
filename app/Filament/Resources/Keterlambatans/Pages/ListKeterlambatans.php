<?php

namespace App\Filament\Resources\Keterlambatans\Pages;

use App\Filament\Resources\Keterlambatans\KeterlambatanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListKeterlambatans extends ListRecords
{
    protected static string $resource = KeterlambatanResource::class;

    protected function getHeaderActions(): array
    {
        // Superuser tidak bisa buat pengajuan
        if (auth()->user()->isSuperuser()) {
            return [];
        }

        return [
            CreateAction::make(),
        ];
    }
}
