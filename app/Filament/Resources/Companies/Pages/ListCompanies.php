<?php

namespace App\Filament\Resources\Companies\Pages;

use App\Filament\Resources\Companies\CompanyResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListCompanies extends ListRecords
{
    protected static string $resource = CompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('trash')
                ->label('Trash')
                ->icon(Heroicon::OutlinedTrash)
                ->color('danger')
                ->tooltip('View deleted companies')
                ->url(fn (): string => CompanyResource::getUrl('index', [
                    // ListRecords maps table filters to the `filters` URL key.
                    // The string is normalized by Livewire to boolean false,
                    // which is the `Only trashed` state of TrashedFilter.
                    'filters' => [
                        'trashed' => ['value' => 'false'],
                    ],
                ])),
        ];
    }
}
