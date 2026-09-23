<?php

namespace App\Filament\Resources\Companies\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CompanyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nama')
                    ->required(),
                TextInput::make('kode')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->validationMessages([
                        'unique' => 'Kode perusahaan sudah digunakan, termasuk oleh data yang dihapus. Buka filter Trash untuk memulihkan data tersebut.',
                    ])
                    ->maxLength(50),
                TextInput::make('npwp')
                    ->mask('99.999.999.9-999.999')
                    ->maxLength(20),
                Textarea::make('alamat')
                    ->columnSpanFull(),
            ]);
    }
}
