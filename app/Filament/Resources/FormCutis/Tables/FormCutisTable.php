<?php

namespace App\Filament\Resources\FormCutis\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Table;
use Filament\Tables;

class FormCutisTable
{
    public static function configure(Table $table): Table
    {
 return $table
            ->columns([

                Tables\Columns\TextColumn::make('index')
                    ->label('No.')
                    ->rowIndex(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Employee'),

                Tables\Columns\TextColumn::make('tanggal_mulai')
                    ->date()
                    ->label('Start'),

                Tables\Columns\TextColumn::make('tanggal_selesai')
                    ->date()
                    ->label('End'),

                Tables\Columns\TextColumn::make('jumlah_hari')
                    ->label('Days'),

Tables\Columns\BadgeColumn::make('approval_level')
    ->label('Status')
    ->formatStateUsing(fn ($state) =>
        match ($state) {
            -1 => 'Rejected',
            0 => 'Waiting Manager',
            1 => 'Waiting HRD',
            2 => 'Approved',
        }
    ),

                Tables\Columns\TextColumn::make('expired_at')
                    ->label('Expired')
                    ->date()
                    ->color(fn ($record) =>
                        $record->expired_at && $record->expired_at->isPast()
                            ? 'danger'
                            : 'success'
                    ),

            ])
            ->actions([

                Tables\Actions\EditAction::make(),

                DeleteAction::make(),

            ]);
    }
}
