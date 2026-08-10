<?php

namespace App\Filament\Resources\SuratPerintahBayars\Schemas;

use App\Models\Company;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SuratPerintahBayarForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema

            ->disabled(fn ($record) => $record?->isLocked())

            ->schema([

                /*
                |--------------------------------------------------------------------------
                | COMPANY
                |--------------------------------------------------------------------------
                */

                Select::make('company_id')
                    ->label('Company')
                    ->options(Company::pluck('nama', 'id'))
                    ->searchable()
                    ->required(),

                /*
                |--------------------------------------------------------------------------
                | INVOICE INFO
                |--------------------------------------------------------------------------
                */

                TextInput::make('no_invoice')
                    ->label('Invoice Number')
                    ->required()
                    ->maxLength(255),

                TextInput::make('customer')
                    ->label('Company Name')
                    ->required()
                    ->maxLength(255),

                DatePicker::make('tanggal_penagihan')
                    ->label('Billing Date')
                    ->required(),

                DatePicker::make('tanggal_jatuhtempo')
                    ->label('Due Date')
                    ->required(),

                /*
                |--------------------------------------------------------------------------
                | AMOUNT
                |--------------------------------------------------------------------------
                */

                TextInput::make('jumlah')
                    ->label('Amount')
                    ->numeric()
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($get, $set) => self::calculateAll($get, $set)),

                TextInput::make('ppn')
                    ->label('VAT (11%)')
                    ->numeric()
                    ->readOnly()
                    ->hint('Auto')
                    ->hintColor('success')
                    ->helperText('Auto-calculated: 11% VAT'),

                TextInput::make('pph')
                    ->label('Withholding Tax (PPH)')
                    ->numeric()
                    ->default(0)
                    ->live(onBlur: true)
                    ->hint('Manual')
                    ->hintColor('warning')
                    ->helperText('Enter the applicable withholding tax (e.g. Article 21 or 23).')
                    ->afterStateUpdated(fn ($get, $set) => self::calculateTotal($get, $set)),

                TextInput::make('admin')
                    ->label('Admin Fee')
                    ->numeric()
                    ->default(0)
                    ->live(onBlur: true)
                    ->hint('Manual')
                    ->hintColor('warning')
                    ->afterStateUpdated(fn ($get, $set) => self::calculateTotal($get, $set)),

                TextInput::make('jumlah_total')
                    ->label('Amount Billed')
                    ->numeric()
                    ->readOnly()
                    ->hint('Auto')
                    ->hintColor('success')
                    ->helperText('Auto-calculated from all fields above'),

                /*
                |--------------------------------------------------------------------------
                | DETAILS
                |--------------------------------------------------------------------------
                */

                TextInput::make('terbilang')
                    ->label('In Words')
                    ->readOnly()
                    ->hint('Auto')
                    ->hintColor('success')
                    ->maxLength(255),

                TextInput::make('pembayaran_tahap')
                    ->label('Payment Stage')
                    ->numeric()
                    ->maxLength(255),

                TextInput::make('jumlah_lampiran')
                    ->label('Number of Attachments')
                    ->numeric()
                    ->maxLength(255),

                Textarea::make('keterangan')
                    ->label('Description')
                    ->columnSpanFull(),

                Textarea::make('informasi_transfer')
                    ->label('Transfer Information')
                    ->columnSpanFull(),

                /*
                |--------------------------------------------------------------------------
                | FILE UPLOAD
                |--------------------------------------------------------------------------
                */

                FileUpload::make('lampiran')
                    ->label('Attachment')
                    ->directory('surat-perintah-bayar')
                    ->visibility('public')
                    ->disk('public')
                    ->preserveFilenames()
                    ->downloadable()
                    ->openable()
                    ->required(),

            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | AUTO CALCULATE PPN & TOTAL
    |--------------------------------------------------------------------------
    */

    protected static function calculateAll($get, $set): void
    {
        $jumlah = (float) ($get('jumlah') ?? 0);

        // PPN = 11% dari jumlah
        $ppn = $jumlah * 0.11;

        $set('ppn', round($ppn));

        self::calculateTotal($get, $set, $ppn);
    }

    protected static function calculateTotal($get, $set, ?float $ppn = null): void
    {
        $jumlah = (float) ($get('jumlah') ?? 0);
        $ppn = $ppn ?? (float) ($get('ppn') ?? 0);
        $pph = (float) ($get('pph') ?? 0);
        $admin = (float) ($get('admin') ?? 0);

        $total = $jumlah + $ppn + $admin - $pph;

        $set('jumlah_total', round($total));
        $set('terbilang', self::terbilang((int) $total).' Rupiah');
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
            return self::terbilang($angka - 10).'teen';
        } elseif ($angka < 100) {
            $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

            return $tens[(int) ($angka / 10)].($angka % 10 ? ' '.self::terbilang($angka % 10) : '');
        } elseif ($angka < 200) {
            return 'One Hundred'.($angka % 100 ? ' '.self::terbilang($angka % 100) : '');
        } elseif ($angka < 1000) {
            return self::terbilang((int) ($angka / 100)).' Hundred'.($angka % 100 ? ' '.self::terbilang($angka % 100) : '');
        } elseif ($angka < 2000) {
            return 'One Thousand'.($angka % 1000 ? ' '.self::terbilang($angka % 1000) : '');
        } elseif ($angka < 1000000) {
            return self::terbilang((int) ($angka / 1000)).' Thousand'.($angka % 1000 ? ' '.self::terbilang($angka % 1000) : '');
        } elseif ($angka < 1000000000) {
            return self::terbilang((int) ($angka / 1000000)).' Million'.($angka % 1000000 ? ' '.self::terbilang($angka % 1000000) : '');
        } elseif ($angka < 1000000000000) {
            return self::terbilang((int) ($angka / 1000000000)).' Billion'.($angka % 1000000000 ? ' '.self::terbilang($angka % 1000000000) : '');
        }

        return '';
    }
}
