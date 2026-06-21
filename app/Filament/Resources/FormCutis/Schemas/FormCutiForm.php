<?php

namespace App\Filament\Resources\FormCutis\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\KuotaCuti;

class FormCutiForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
Forms\Components\Select::make('tahun')
    ->label('Year')
    ->options(fn () =>
        KuotaCuti::where('user_id', auth()->id())
            ->pluck('tahun', 'tahun')
            ->toArray()
    )
    ->live() // ✅ WAJIB di v5
->afterStateUpdated(function ($state, callable $set) {

    if (! $state) {
        $set('sisa_cuti', null);
        return;
    }

    $kuota = KuotaCuti::query()
        ->where('user_id', auth()->id())
        ->where('tahun', (int) $state)
        ->first();

    if (! $kuota) {
        $set('sisa_cuti', 0);
        return;
    }
$set('sisa_cuti', $kuota->sisa_cuti);

})

    ->required(),


Forms\Components\TextInput::make('sisa_cuti')
    ->label('Remaining Leave')
    ->readOnly()
    ->suffix('days')
    ->dehydrated(false)
    ->live()
    ->reactive(),

        Forms\Components\Select::make('jenis_cuti')
        ->options([
        'Cuti Tahunan' => 'Cuti Tahunan',
        'Cuti Sakit' => 'Cuti Sakit',
        'Cuti Melahirkan' => 'Cuti Melahirkan',
        'Cuti Keguguran' => 'Cuti Keguguran',
        'Cuti Khusus' => 'Cuti Khusus',
        'Cuti Besar' => 'Cuti Besar',
        'Cuti Haid' => 'Cuti Haid',
                    ])
        ->required()
        ->reactive(),

                Forms\Components\DatePicker::make('tanggal_mulai')
                    ->label('Start Date')
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn ($state, callable $set, callable $get) =>
                        self::calculateDays($get, $set)
                    ),

                Forms\Components\DatePicker::make('tanggal_selesai')
                    ->label('End Date')
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn ($state, callable $set, callable $get) =>
                        self::calculateDays($get, $set)
                    ),

    Forms\Components\TextInput::make('jumlah_hari')
    ->label('Total Days')
    ->numeric()
    ->readOnly()
    ->dehydrated(true)
    ->required(),

                Forms\Components\Textarea::make('alasan')
                    ->required()
                    ->columnSpanFull(),

    Forms\Components\FileUpload::make('lampiran')
    ->label('Lampiran (Wajib untuk Cuti Sakit)')
    ->disk('public')
    ->directory('lampiran-cuti')
    ->acceptedFileTypes([
        'application/pdf',
        'image/jpeg',
        'image/png',
    ])
    ->maxSize(2048)
    ->visible(fn ($get) => $get('jenis_cuti') === 'Cuti Sakit')
    ->required(fn ($get) => $get('jenis_cuti') === 'Cuti Sakit'),

            ]);
    }

    public static function calculateDays($get, $set)
{
    if ($get('tanggal_mulai') && $get('tanggal_selesai')) {

        $start = \Carbon\Carbon::parse($get('tanggal_mulai'));
        $end = \Carbon\Carbon::parse($get('tanggal_selesai'));

        if ($end->lt($start)) {
            $set('jumlah_hari', 0);
            return;
        }

        $days = $start->diffInDays($end) + 1;

        $set('jumlah_hari', $days);
    }
}


}
