<?php

namespace App\Filament\Resources\KuotaCutis\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class KuotaCutisTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('tahun')
                    ->label('Year')
                    ->sortable(),

                TextColumn::make('kuota_tahunan')
                    ->label('Annual Quota')
                    ->sortable(),

                TextColumn::make('cuti_terpakai')
                    ->label('Used Leave')
                    ->sortable(),

                TextColumn::make('sisa_cuti')
                    ->label('Remaining Leave')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state <= 0 => 'danger',
                        $state <= 3 => 'warning',
                        default => 'success',
                    })
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                  EditAction::make(),
              ])
            ->toolbarActions([
                  BulkActionGroup::make([
                    DeleteBulkAction::make(),
                  ]),
              ]);
    }
}
