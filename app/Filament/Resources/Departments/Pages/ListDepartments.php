<?php

namespace App\Filament\Resources\Departments\Pages;

use App\Filament\Resources\Departments\DepartmentResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListDepartments extends ListRecords
{
    protected static string $resource = DepartmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('trash')
                ->label('Trash')
                ->icon(Heroicon::OutlinedTrash)
                ->color('danger')
                ->tooltip('View deleted departments')
                ->url(fn (): string => DepartmentResource::getUrl('index', [
                    'filters' => [
                        'trashed' => ['value' => 'false'],
                    ],
                ])),
        ];
    }
}
