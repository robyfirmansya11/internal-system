<?php

namespace App\Filament\Resources\NotaPenggantianBiayas\Schemas;

use App\Models\Company;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class NotaPenggantianBiayaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->disabled(fn ($record) => $record?->isLocked())
            ->columns(2)
            ->schema([
                Select::make('company_id')
                    ->label('PT / Company')
                    ->options(Company::query()->orderBy('nama')->pluck('nama', 'id'))
                    ->searchable()
                    ->required(),

                DatePicker::make('tanggal')
                    ->label('Date')
                    ->required(),

                Textarea::make('keterangan')->hidden()->default(''),
                TextInput::make('jumlah')->hidden()->default(0),

                Repeater::make('details')
                    ->label('Expense Details')
                    ->relationship('details')
                    ->defaultItems(1)
                    ->minItems(1)
                    ->maxItems(5)
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn ($state, callable $set, callable $get) => self::calculateTotal($get, $set))
                    ->schema([
                        Textarea::make('keterangan')
                            ->label('Description')
                            ->required()
                            ->columnSpan(2),
                        TextInput::make('jumlah')
                            ->label('Amount')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn ($state, callable $set, callable $get) => self::calculateTotal($get, $set)),
                    ])
                    ->columns(3)
                    ->createItemButtonLabel('Add Expense Detail')
                    ->columnSpanFull(),

                TextInput::make('jumlah_total')
                    ->label('Total Amount')
                    ->numeric()
                    ->prefix('Rp')
                    ->readOnly()
                    ->required(),

                TextInput::make('terbilang')
                    ->label('In Words')
                    ->readOnly()
                    ->columnSpanFull(),

                Textarea::make('informasi_transfer')
                    ->label('Fund Transfer Information')
                    ->columnSpanFull(),

                TextInput::make('jumlah_lampiran')
                    ->label('Number of Attachments')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->required(),

                FileUpload::make('lampiran')
                    ->label('Attachment')
                    ->directory('nota-penggantian-biaya')
                    ->disk('public')
                    ->visibility('public')
                    ->preserveFilenames()
                    ->downloadable()
                    ->openable(),
            ]);
    }

    protected static function calculateTotal($get, $set): void
    {
        $details = $get('../../details') ?? [];
        $total = collect($details)->sum(fn (array $detail) => (float) ($detail['jumlah'] ?? 0));
        $total = round($total);

        $set('../../jumlah', $total);
        $set('../../jumlah_total', $total);
        $set('../../terbilang', self::terbilang((int) $total).' Rupiah');
    }

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
