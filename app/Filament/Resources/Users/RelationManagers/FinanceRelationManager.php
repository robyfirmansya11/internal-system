<?php

namespace App\Filament\Resources\Users\RelationManagers;

use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FinanceRelationManager extends RelationManager
{
    protected static string $relationship = 'finance';

    protected static ?string $title = 'Finance';

    protected static bool $isLazy = false;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('gaji_pokok')
                    ->numeric(),
                TextInput::make('tunjangan')
                    ->numeric(),
                TextInput::make('bank_name'),
                TextInput::make('no_rekening'),
                TextInput::make('npwp'),
                TextInput::make('bpjs_kesehatan'),
                TextInput::make('bpjs_ketenagakerjaan'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('bank_name')
            ->columns([
                TextColumn::make('user_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('gaji_pokok')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('tunjangan')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('bank_name')
                    ->searchable(),
                TextColumn::make('no_rekening')
                    ->searchable(),
                TextColumn::make('npwp')
                    ->searchable(),
                TextColumn::make('bpjs_kesehatan')
                    ->searchable(),
                TextColumn::make('bpjs_ketenagakerjaan')
                    ->searchable(),
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
            ->headerActions([
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([

            ]);
    }
}
