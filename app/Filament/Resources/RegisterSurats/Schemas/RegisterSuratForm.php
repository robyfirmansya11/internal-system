<?php

namespace App\Filament\Resources\RegisterSurats\Schemas;

use App\Models\Company;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class RegisterSuratForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([

                /**
                 * ---------------------------------------------------------
                 * LETTER INFORMATION
                 * ---------------------------------------------------------
                 */
                DatePicker::make('tanggal_surat')
                    ->label('Letter Date')
                    ->required(),

                Select::make('company_id')
                    ->label('Company')
                    ->options(
                        Company::pluck('nama', 'id')
                    )
                    ->searchable()
                    ->required(),

                TextInput::make('no_surat')
                    ->label('Letter Number')
                    ->placeholder('Example: 001/BMU-LGL/III/2026')
                    ->helperText('Format: Number/Company-Division/Month(Roman)/Year')
                    ->required()
                    ->unique(ignoreRecord: true),

                TextInput::make('ditujukan')
                    ->label('Recipient')
                    ->required(),

                Textarea::make('keterangan')
                    ->label('Description')
                    ->columnSpanFull(),

                /**
                 * ---------------------------------------------------------
                 * ATTACHMENT
                 * ---------------------------------------------------------
                 */
                FileUpload::make('lampiran_surat')
                    ->label('Attachment')
                    ->directory('register-surat')
                    ->visibility('public')
                    ->preserveFilenames()
                    ->disk('public')
                    ->downloadable()
                    ->openable()
                    ->required(),

            ]);
    }
}
