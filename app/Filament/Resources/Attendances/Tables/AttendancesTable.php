<?php

namespace App\Filament\Resources\Attendances\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AttendancesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->searchable(),
                TextColumn::make('clock_in')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('clock_in_lat')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('clock_in_lng')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('clock_in_photo')
                    ->searchable(),
                TextColumn::make('clock_in_address')
                    ->searchable(),
                TextColumn::make('clock_out')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('clock_out_lat')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('clock_out_lng')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('clock_out_photo')
                    ->searchable(),
                TextColumn::make('clock_out_address')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('date')
                    ->date()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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
