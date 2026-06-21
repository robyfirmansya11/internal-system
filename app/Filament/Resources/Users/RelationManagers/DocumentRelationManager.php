<?php

namespace App\Filament\Resources\Users\RelationManagers;

use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DocumentRelationManager extends RelationManager
{
    protected static string $relationship = 'document';

    protected static ?string $title = 'Documents';

    protected static bool $isLazy = false;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\FileUpload::make('ktp_file')
                    ->disk('public')
                    ->directory('documents/ktp')
                    ->visibility('public'),
                Forms\Components\FileUpload::make('kk_file')
                    ->disk('public')
                    ->directory('documents/kk')
                    ->visibility('public'),
                Forms\Components\FileUpload::make('cv_file')
                    ->disk('public')
                    ->directory('documents/cv')
                    ->visibility('public'),
                Forms\Components\FileUpload::make('ijazah_file')
                    ->disk('public')
                    ->directory('documents/ijazah')
                    ->visibility('public'),
                Forms\Components\FileUpload::make('kontrak_file')
                    ->disk('public')
                    ->directory('documents/kontrak')
                    ->visibility('public'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('ktp_file')
            ->columns([
                TextColumn::make('user_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('ktp_file')
                    ->label('KTP')
                    ->url(fn ($record) => asset('storage/'.$record->ktp_file))
                    ->openUrlInNewTab(),
                TextColumn::make('kk_file')
                    ->label('KK')
                    ->url(fn ($record) => asset('storage/'.$record->kk_file))
                    ->openUrlInNewTab(),
                TextColumn::make('cv_file')
                    ->label('CV')
                    ->url(fn ($record) => asset('storage/'.$record->cv_file))
                    ->openUrlInNewTab(),
                TextColumn::make('ijazah_file')
                    ->label('Ijazah')
                    ->url(fn ($record) => asset('storage/'.$record->ijazah_file))
                    ->openUrlInNewTab(),
                TextColumn::make('kontrak_file')
                    ->label('Kontrak')
                    ->url(fn ($record) => asset('storage/'.$record->kontrak_file))
                    ->openUrlInNewTab(),
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
