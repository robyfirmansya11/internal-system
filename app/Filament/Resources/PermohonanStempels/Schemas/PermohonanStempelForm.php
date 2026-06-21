<?php

namespace App\Filament\Resources\PermohonanStempels\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Schema;
use App\Models\Company;

class PermohonanStempelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema

            // 🔒 Lock kalau sudah approve/reject
            ->disabled(fn ($record) => $record?->isLocked())

            ->schema([

                DatePicker::make('tanggal')
                    ->required(),

                    Select::make('company_id')
                    ->label('Company / PT')
                    ->options(
                        Company::pluck('nama', 'id')
                    )
                    ->searchable()
                    ->required(),

                TextInput::make('nomor_surat')
                    ->required(),

                TextInput::make('tujuan')
                    ->required(),


                TextInput::make('ditandatangani_oleh')
                    ->required(),

                Textarea::make('keterangan')
                    ->columnSpanFull(),

                DatePicker::make('tanggal_surat'),

                DatePicker::make('tanggal_stempel'),


                FileUpload::make('lampiran')
                    ->directory('permohonan-stempel')
                    ->visibility('public')
                    ->preserveFilenames()
                    ->disk('public')
                    ->downloadable()
                    ->openable()
                    ->required(),

            ]);
    }
}
