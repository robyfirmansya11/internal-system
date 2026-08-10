<?php

namespace App\Filament\Resources\OfficeLocations\Pages;

use App\Filament\Resources\OfficeLocations\OfficeLocationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOfficeLocation extends CreateRecord
{
    protected static string $resource = OfficeLocationResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
