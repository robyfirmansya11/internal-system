<?php

namespace App\Filament\Resources\NotaPenggantianBiayas\Pages;

use App\Filament\Resources\NotaPenggantianBiayas\NotaPenggantianBiayaResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditNotaPenggantianBiaya extends EditRecord
{
    protected static string $resource = NotaPenggantianBiayaResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make(), ForceDeleteAction::make(), RestoreAction::make()];
    }

    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
}
