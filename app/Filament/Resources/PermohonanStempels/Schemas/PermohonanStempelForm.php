<?php

namespace App\Filament\Resources\PermohonanStempels\Schemas;

use App\Models\Company;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PermohonanStempelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema

            // 🔒 Lock kalau sudah approve/reject
            ->disabled(fn ($record) => $record?->isLocked())

            ->schema([

                DatePicker::make('tanggal')
                    ->label('Date')
                    ->required(),

                Select::make('company_id')
                    ->label('Company')
                    ->options(
                        Company::pluck('nama', 'id')
                    )
                    ->searchable()
                    ->required(),

                TextInput::make('nomor_surat')
                    ->label('Letter Number')
                    ->required(),

                TextInput::make('tujuan')
                    ->label('Purpose')
                    ->required(),

                TextInput::make('ditandatangani_oleh')
                    ->label('Signed By')
                    ->required(),

                Textarea::make('keterangan')
                    ->label('Description / Notes')
                    ->columnSpanFull(),

                DatePicker::make('tanggal_surat')
                    ->label('Letter Date'),

                DatePicker::make('tanggal_stempel')
                    ->label('Stamp Date'),

                FileUpload::make('lampiran')
                    ->label('Attachment')
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
