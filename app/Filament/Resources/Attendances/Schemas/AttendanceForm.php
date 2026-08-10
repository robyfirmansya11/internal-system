<?php

namespace App\Filament\Resources\Attendances\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class AttendanceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                DateTimePicker::make('clock_in'),
                TextInput::make('clock_in_lat')
                    ->numeric(),
                TextInput::make('clock_in_lng')
                    ->numeric(),
                TextInput::make('clock_in_photo'),
                TextInput::make('clock_in_address'),
                DateTimePicker::make('clock_out'),
                TextInput::make('clock_out_lat')
                    ->numeric(),
                TextInput::make('clock_out_lng')
                    ->numeric(),
                TextInput::make('clock_out_photo'),
                TextInput::make('clock_out_address'),
                Select::make('status')
                    ->options(['present' => 'Present', 'late' => 'Late', 'absent' => 'Absent'])
                    ->default('present')
                    ->required(),
                Textarea::make('note')
                    ->columnSpanFull(),
                DatePicker::make('date')
                    ->required(),
            ]);
    }
}
