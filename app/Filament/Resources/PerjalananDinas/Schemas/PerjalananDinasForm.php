<?php

namespace App\Filament\Resources\PerjalananDinas\Schemas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Select;

class PerjalananDinasForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

   /*
                |--------------------------------------------------------------------------
                | INFORMASI PENGAJUAN
                |--------------------------------------------------------------------------
                */

                Section::make('Informasi Pengajuan')
                    ->disabled(fn ($record) => $record?->isLocked())
                    ->schema([

                        Select::make('company_id')
                            ->relationship('company', 'nama')
                            ->required(),

                        Textarea::make('keterangan')
                            ->required()
                            ->columnSpanFull(),

                        TextInput::make('jumlah_lampiran')
                            ->numeric()
                            ->default(0),

                    ])
                    ->columns(3),

                /*
                |--------------------------------------------------------------------------
                | DETAIL PERJALANAN
                |--------------------------------------------------------------------------
                */

                Section::make('Detail Perjalanan')
                    ->disabled(fn ($record) => $record?->isLocked())
                    ->schema([

                        Repeater::make('details')
                            ->relationship()
                            ->defaultItems(1)
                            ->maxItems(5)
                            ->live(true)
                            ->afterStateUpdated(fn ($get,$set) => self::calculateTotal($get,$set))
                            ->schema([

                                DatePicker::make('tanggal_berangkat')->required(),
                                TimePicker::make('waktu_berangkat'),
                                TextInput::make('tempat_berangkat')->required(),

                                DatePicker::make('tanggal_tujuan')->required(),
                                TimePicker::make('waktu_tujuan'),
                                TextInput::make('tempat_tujuan')->required(),

                                TextInput::make('jumlah_hari')
                                    ->numeric()
                                    ->default(1)
                                    ->reactive()
                                    /* ->live(onBlur: true) */
                                    ->afterStateUpdated(fn ($get,$set) => self::calculateTotal($get,$set)),

                                TextInput::make('amount_transportasi')
                                    ->numeric()
                                    ->default(0)
                                    ->reactive()
                                    /* ->live(onBlur: true) */
                                    ->afterStateUpdated(fn ($get,$set) => self::calculateTotal($get,$set)),

                                TextInput::make('amount_tunjangan')
                                    ->numeric()
                                    ->default(0)
                                    ->reactive()
                                   /*  ->live(onBlur: true) */
                                    ->afterStateUpdated(fn ($get,$set) => self::calculateTotal($get,$set)),

                                TextInput::make('lama_hotel')
                                    ->numeric()
                                    ->default(0)
                                    ->reactive()
                                 /*    ->live(onBlur: true) */
                                    ->afterStateUpdated(fn ($get,$set) => self::calculateTotal($get,$set)),

                                TextInput::make('amount_hotel')
                                    ->numeric()
                                    ->default(0)
                                    ->reactive()
                                   /*  ->live(onBlur: true) */
                                    ->afterStateUpdated(fn ($get,$set) => self::calculateTotal($get,$set)),

                                TextInput::make('misc')
                                    ->numeric()
                                    ->default(0)
                                    ->reactive()
                                  /*   ->live(onBlur: true) */
                                    ->afterStateUpdated(fn ($get,$set) => self::calculateTotal($get,$set)),

                                TextInput::make('amount_other')
                                    ->numeric()
                                    ->default(0)
                                    ->reactive()
                                 /*    ->live(onBlur: true) */
                                    ->afterStateUpdated(fn ($get,$set) => self::calculateTotal($get,$set)),

                            ])
                            ->columns(3)
                            ->createItemButtonLabel('Tambah Perjalanan'),

                    ])
                    ->columnSpanFull(),

                /*
                |--------------------------------------------------------------------------
                | CATATAN
                |--------------------------------------------------------------------------
                */

                Section::make('Catatan')
                    ->disabled(fn ($record) => $record?->isLocked())
                    ->schema([

                        TextInput::make('total')
                            ->numeric()
                            ->prefix('Rp')
                            ->readOnly()
                            ->hint('Auto')
                            ->hintColor('success'),

                        Textarea::make('terbilang')
                            ->readOnly()
                            ->hint('Auto')
                            ->hintColor('success')
                            ->columnSpanFull(),

                        Textarea::make('catatan')
                            ->columnSpanFull(),

                    ]),
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | HITUNG TOTAL
    |--------------------------------------------------------------------------
    */

 protected static function calculateTotal($get, $set)
{
    $details = $get('../../details') ?? [];

    $total = 0;

    foreach ($details as $item) {

        $hari = (float) ($item['jumlah_hari'] ?? 0);

        $transport = (float) ($item['amount_transportasi'] ?? 0);

        $tunjangan = (float) ($item['amount_tunjangan'] ?? 0) * $hari;

        $hotel = (float) ($item['amount_hotel'] ?? 0) * (float) ($item['lama_hotel'] ?? 0);

        $misc = (float) ($item['misc'] ?? 0);

        $other = (float) ($item['amount_other'] ?? 0);

        $total += $transport + $tunjangan + $hotel + $misc + $other;
    }

    $set('../../total', round($total));
    $set('../../terbilang', self::terbilang((int) $total) . ' Rupiah');
}

    /*
    |--------------------------------------------------------------------------
    | TERBILANG
    |--------------------------------------------------------------------------
    */

    public static function terbilang(int $angka): string
    {
        $angka = abs($angka);
        $words = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven'];

        if ($angka < 12) {
            return $words[$angka];
        } elseif ($angka < 20) {
            return self::terbilang($angka - 10) . 'teen';
        } elseif ($angka < 100) {
            $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
            return $tens[(int)($angka / 10)] . ($angka % 10 ? ' ' . self::terbilang($angka % 10) : '');
        } elseif ($angka < 1000) {
            return self::terbilang((int)($angka / 100)) . ' Hundred' . ($angka % 100 ? ' ' . self::terbilang($angka % 100) : '');
        } elseif ($angka < 1000000) {
            return self::terbilang((int)($angka / 1000)) . ' Thousand' . ($angka % 1000 ? ' ' . self::terbilang($angka % 1000) : '');
        }

        return '';
    }
}
