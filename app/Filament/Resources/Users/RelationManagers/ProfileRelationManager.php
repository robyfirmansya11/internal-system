<?php

namespace App\Filament\Resources\Users\RelationManagers;

use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProfileRelationManager extends RelationManager
{
    protected static string $relationship = 'profile';

    protected static ?string $title = 'Profile';

    protected static bool $isLazy = false;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nik'),
                TextInput::make('employee_id'),
                TextInput::make('tempat_lahir'),
                DatePicker::make('tanggal_lahir'),
                TextInput::make('jenis_kelamin'),
                Textarea::make('alamat')
                    ->columnSpanFull(),
                TextInput::make('no_hp'),
                TextInput::make('status_pernikahan'),
                TextInput::make('agama'),
                TextInput::make('kewarganegaraan'),
                TextInput::make('status_karyawan'),
                DatePicker::make('tanggal_masuk'),
                DatePicker::make('tanggal_keluar'),
                TextInput::make('lokasi_kerja'),
                FileUpload::make('barcode_signature')
                    ->image()
                    ->disk('public')
                    ->directory('barcode_signatures')
                    ->visibility('public'),
                FileUpload::make('foto')
                    ->label('Foto')
                    ->image()
                    ->disk('public')
                    ->directory('users')
                    ->imageEditor()
                    ->visibility('public'),
            ]);
    }

    protected function afterCreate(): void
    {
        $path = $this->record->profile?->foto;

        if ($path) {

            $fullPath = Storage::disk('public')->path($path);

            Image::read($fullPath)
                ->cover(400, 400)
                ->toJpeg(80)
                ->save($fullPath);
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nik')
            ->columns([
                TextColumn::make('nik')
                    ->searchable(),
                TextColumn::make('employee_id')
                    ->searchable(),
                TextColumn::make('tempat_lahir')
                    ->searchable(),
                TextColumn::make('tanggal_lahir')
                    ->date()
                    ->sortable(),
                TextColumn::make('jenis_kelamin')
                    ->searchable(),
                TextColumn::make('no_hp')
                    ->searchable(),
                TextColumn::make('status_pernikahan')
                    ->searchable(),
                TextColumn::make('agama')
                    ->searchable(),
                TextColumn::make('kewarganegaraan')
                    ->searchable(),
                TextColumn::make('status_karyawan')
                    ->searchable(),
                TextColumn::make('tanggal_masuk')
                    ->date()
                    ->sortable(),
                TextColumn::make('tanggal_keluar')
                    ->date()
                    ->sortable(),
                TextColumn::make('lokasi_kerja')
                    ->searchable(),
                ImageColumn::make('barcode_signature')
                    ->disk('public')
                    ->square()
                    ->size(80),
                ImageColumn::make('foto')
                    ->label('Foto')
                    ->disk('public')
                    ->circular()
                    ->size(80),
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
