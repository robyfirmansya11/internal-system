<?php

namespace App\Filament\Resources\FormCutis\Schemas;

use App\Models\KuotaCuti;
use Filament\Forms;
use Filament\Schemas\Schema;

class FormCutiForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('tahun')
                    ->label('Year')
                    ->options(fn () => KuotaCuti::where('user_id', auth()->id())
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
                    ->label('Leave Balance')
                    ->readOnly()
                    ->suffix('days')
                    ->dehydrated(false)
                    ->live()
                    ->reactive(),

                Forms\Components\Select::make('jenis_cuti')
                    ->options([
                        'Cuti Tahunan' => 'Annual Leave',
                        'Cuti Sakit' => 'Sick Leave',
                        'Cuti Melahirkan' => 'Maternity Leave',
                        'Cuti Keguguran' => 'Miscarriage Leave',
                        'Cuti Khusus' => 'Special Leave',
                        'Cuti Besar' => 'Extended Leave',
                        'Cuti Haid' => 'Menstrual Leave',
                    ])
                    ->required()
                    ->label('Leave Type')
                    ->live()
                    ->reactive(),

                Forms\Components\DatePicker::make('tanggal_mulai')
                    ->label('Start Date')
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn ($state, callable $set, callable $get) => self::calculateDays($get, $set)
                    ),

                Forms\Components\DatePicker::make('tanggal_selesai')
                    ->label('End Date')
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn ($state, callable $set, callable $get) => self::calculateDays($get, $set)
                    ),

                Forms\Components\TextInput::make('jumlah_hari')
                    ->label('Leave Duration')
                    ->numeric()
                    ->readOnly()
                    ->dehydrated(true)
                    ->required(),

                // Keterangan aturan jenis cuti — muncul otomatis begitu
                // jenis_cuti dipilih, ditaruh tepat di atas field "alasan".
                Forms\Components\Placeholder::make('jenis_cuti_rule')
                    ->hiddenLabel()
                    ->content(fn ($get) => self::leaveTypeRule($get('jenis_cuti')))
                    ->visible(fn ($get) => filled($get('jenis_cuti')))
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('alasan')
                    ->label('Reason for Leave')
                    ->required()
                    ->columnSpanFull(),

                Forms\Components\FileUpload::make('lampiran')
                    ->label('Supporting Attachment')
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

    /**
     * Teks aturan per jenis cuti — mengikuti persis logika
     * FormCuti::validateJenisCuti() di model, supaya UI dan
     * validasi backend selalu konsisten satu sama lain.
     */
    public static function leaveTypeRule(?string $jenisCuti): string
    {
        return match ($jenisCuti) {
            'Cuti Haid' => '📌 Menstrual Leave: Maximum of 2 calendar days.',
            'Cuti Khusus' => '📌 Special Leave: May only be requested for 1 to 3 calendar days.',
            'Cuti Melahirkan' => '📌 Maternity Leave: Maximum of 90 calendar days.',
            'Cuti Keguguran' => '📌 Miscarriage Leave: Maximum of 45 calendar days.',
            'Cuti Sakit' => '📌 Sick Leave: A medical certificate or supporting document is required.',
            'Cuti Tahunan' => '📌 Annual Leave: This request will be deducted from your available leave balance.',
            'Cuti Besar' => '📌 Extended Leave: Subject to the company`s leave policy.',
            default => '',
        };
    }
}
