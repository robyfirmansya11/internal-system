<?php

namespace App\Filament\Resources\Kasbons\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Filament\Forms\Components\FileUpload;
use App\Models\Company;

class KasbonForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema

            ->disabled(fn ($record) => $record?->isLocked())

            ->schema([

                DatePicker::make('tanggal')
                    ->label('Tanggal')
                    ->required(),

                Select::make('company_id')
                    ->label('Company / PT')
                    ->options(Company::pluck('nama', 'id'))
                    ->searchable()
                    ->required(),

                TextInput::make('jumlah_dana')
                    ->label('Jumlah Dana (Rp)')
                    ->numeric()
                    ->prefix('Rp')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, callable $set) {
                        if (! $state) return;
                        $set('terbilang', self::terbilang((int) $state) . ' Rupiah');
                    }),

                TextInput::make('terbilang')
                    ->label('Terbilang')
                    ->required()
                    ->readOnly(),

                Textarea::make('keterangan')
                    ->label('Keterangan')
                    ->columnSpanFull(),

                Textarea::make('informasi_transfer')
                    ->label('Informasi Transfer')
                    ->placeholder('Contoh: BCA 1234567890 a.n. John Doe')
                    ->columnSpanFull(),

                FileUpload::make('lampiran')
                    ->label('Lampiran')
                    ->directory('kasbon')
                    ->visibility('public')
                    ->preserveFilenames()
                    ->disk('public')
                    ->downloadable()
                    ->openable()
                    ->nullable(),

            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | TERBILANG HELPER
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
    } elseif ($angka < 200) {
        return 'One Hundred' . ($angka % 100 ? ' ' . self::terbilang($angka % 100) : '');
    } elseif ($angka < 1000) {
        return self::terbilang((int)($angka / 100)) . ' Hundred' . ($angka % 100 ? ' ' . self::terbilang($angka % 100) : '');
    } elseif ($angka < 2000) {
        return 'One Thousand' . ($angka % 1000 ? ' ' . self::terbilang($angka % 1000) : '');
    } elseif ($angka < 1000000) {
        return self::terbilang((int)($angka / 1000)) . ' Thousand' . ($angka % 1000 ? ' ' . self::terbilang($angka % 1000) : '');
    } elseif ($angka < 1000000000) {
        return self::terbilang((int)($angka / 1000000)) . ' Million' . ($angka % 1000000 ? ' ' . self::terbilang($angka % 1000000) : '');
    } elseif ($angka < 1000000000000) {
        return self::terbilang((int)($angka / 1000000000)) . ' Billion' . ($angka % 1000000000 ? ' ' . self::terbilang($angka % 1000000000) : '');
    }

    return '';
}
}
