<?php

namespace App\Filament\Resources\NotaPenggantianBiayas\Pages;

use App\Filament\Resources\NotaPenggantianBiayas\NotaPenggantianBiayaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListNotaPenggantianBiayas extends ListRecords
{
    protected static string $resource = NotaPenggantianBiayaResource::class;

    protected function getHeaderActions(): array { return [CreateAction::make()]; }
}
