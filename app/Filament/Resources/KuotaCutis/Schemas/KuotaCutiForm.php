<?php

namespace App\Filament\Resources\KuotaCutis\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class KuotaCutiForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Select::make('user_id')
                    ->label('Employee')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                TextInput::make('tahun')
                    ->numeric()
                    ->required(),

                TextInput::make('kuota_tahunan')
                    ->numeric()
                    ->live()
                    ->required()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {

                        $terpakai = (int) $get('cuti_terpakai');

                        $set(
                            'sisa_cuti',
                            max(0, $state - $terpakai)
                        );

                    }),

                TextInput::make('cuti_terpakai')
                    ->numeric()
                    ->default(0)
                    ->live()
                    ->required()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {

                        $kuota = (int) $get('kuota_tahunan');

                        $set(
                            'sisa_cuti',
                            max(0, $kuota - $state)
                        );

                    }),

                TextInput::make('sisa_cuti')
                    ->label('Remaining Leave')
                    ->readOnly()
                    ->suffix('days')
                    ->dehydrated(false)
                    ->live()
                    ->reactive(),
            ]);

    }
}
