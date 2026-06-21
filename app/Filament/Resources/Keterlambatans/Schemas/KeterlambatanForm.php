<?php

namespace App\Filament\Resources\Keterlambatans\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Schema;

class KeterlambatanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->disabled(fn ($record) => $record?->isLocked())
            ->schema([

                DatePicker::make('tanggal')
                    ->label('Tanggal')
                    ->required()
                    ->native(false)
                    ->maxDate(now()),

                TimePicker::make('jam_masuk')
                    ->label('Jam Masuk')
                    ->required(),

                Textarea::make('alasan')
                    ->label('Alasan Keterlambatan')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),

            ])
            ->columns(2);
    }
}
