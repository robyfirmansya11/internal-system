<?php

namespace App\Filament\Resources\Lemburs\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Schema;

class LemburForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->disabled(fn ($record) => $record && ! $record->isSubmitted())
            ->components([

                DatePicker::make('bulan_lembur')
                    ->label('Overtime Month')
                    ->displayFormat('F Y')
                    ->format('Y-m')
                    ->native(false)
                    ->closeOnDateSelection()
                    ->required(),

                DatePicker::make('tanggal_lembur')
                    ->label('Overtime Date')
                    ->native(false)
                    ->required(),

                TimePicker::make('mulai_kerja')
                    ->label('Work Start')
                    ->seconds(false)
                    ->required(),

                TimePicker::make('selesai_kerja')
                    ->label('Work End')
                    ->seconds(false)
                    ->required(),

                TimePicker::make('mulai_lembur')
                    ->label('Overtime Start')
                    ->seconds(false)
                    ->live()
                    ->required()
                    ->afterStateUpdated(fn ($state, $get, $set) => self::calculateOvertime($get, $set)
                    ),

                TimePicker::make('selesai_lembur')
                    ->label('Overtime End')
                    ->seconds(false)
                    ->live()
                    ->required()
                    ->afterStateUpdated(fn ($state, $get, $set) => self::calculateOvertime($get, $set)
                    ),

                TextInput::make('jumlah_jam_lembur')
                    ->label('Total Overtime Hours')
                    ->numeric()
                    ->readOnly()
                    ->dehydrated()
                    ->suffix('jam')
                    ->required(),

                TextInput::make('uang_makan')
                    ->label('Uang Makan')
                    ->numeric()
                    ->prefix('Rp')
                    ->nullable(),

                Textarea::make('uraian_pekerjaan')
                    ->label('Uraian Pekerjaan')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),

            ])
            ->columns(2);
    }

    private static function calculateOvertime($get, $set): void
    {
        $start = $get('mulai_lembur');
        $end = $get('selesai_lembur');

        if (! $start || ! $end) {
            $set('jumlah_jam_lembur', null);

            return;
        }

        $startTime = strtotime($start);
        $endTime = strtotime($end);

        if ($endTime <= $startTime) {
            $set('jumlah_jam_lembur', 0);

            return;
        }

        $hours = ($endTime - $startTime) / 3600;
        $set('jumlah_jam_lembur', round($hours, 2));
    }
}
