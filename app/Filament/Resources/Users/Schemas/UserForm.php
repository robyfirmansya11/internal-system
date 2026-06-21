<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\Role;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('User Tabs')
                    ->tabs([

                        /*
                        |------------------------------------------------------
                        | ACCOUNT
                        |------------------------------------------------------
                        */

                        Tab::make('Account')
                            ->icon('heroicon-o-user')
                            ->schema([

                                TextInput::make('name')
                                    ->label('Full Name')
                                    ->required()
                                    ->maxLength(100),

                                TextInput::make('email')
                                    ->email()
                                    ->required()
                                    ->unique(ignoreRecord: true),

                                TextInput::make('password')
                                    ->password()
                                    ->revealable()
                                    ->minLength(6)
                                    ->required(fn ($context) => $context === 'create')
                                    ->dehydrateStateUsing(
                                        fn ($state) => filled($state)
                                            ? Hash::make($state)
                                            : null
                                    )
                                    ->dehydrated(fn ($state) => filled($state)),

                                Select::make('level')
                                    ->label('Role')
                                    ->options(
                                        collect(Role::cases())
                                            ->mapWithKeys(fn (Role $r) => [
                                                $r->value => $r->label(),
                                            ])
                                    )
                                    ->required()
                                    ->native(false),

                                Select::make('jabatan')
                                    ->options([
                                        'Staff' => 'Staff',
                                        'Manager' => 'Manager',
                                        'Finance Manager' => 'Finance Manager', // fix typo
                                        'HRD' => 'HRD',
                                        'Vice President' => 'Vice President',
                                        'President Director' => 'President Director',
                                    ])
                                    ->required()
                                    ->native(false),

                                DatePicker::make('tanggal_masuk')
                                    ->required()
                                    ->native(false),

                            ])
                            ->columns(2),

                        /*
                        |------------------------------------------------------
                        | EMPLOYEE PROFILE
                        |------------------------------------------------------
                        */

                        Tab::make('Employee Profile')
                            ->icon('heroicon-o-identification')
                            ->schema([
                                Section::make()
                                    ->relationship('profile')
                                    ->schema([

                                        Select::make('company_id')
                                            ->label('Company')
                                            ->relationship('company', 'nama')
                                            ->searchable()
                                            ->preload()
                                            ->native(false),

                                        TextInput::make('nik')
                                            ->label('NIK'),

                                        TextInput::make('employee_id')
                                            ->label('Employee ID'),

                                        TextInput::make('tempat_lahir')
                                            ->label('Tempat Lahir'),

                                        DatePicker::make('tanggal_lahir')
                                            ->label('Tanggal Lahir')
                                            ->native(false),

                                        Select::make('jenis_kelamin')
                                            ->label('Jenis Kelamin')
                                            ->options([
                                                'Laki-laki' => 'Laki-laki',
                                                'Perempuan' => 'Perempuan',
                                            ])
                                            ->native(false),

                                        Textarea::make('alamat')
                                            ->label('Alamat')
                                            ->columnSpanFull(),

                                        TextInput::make('no_hp')
                                            ->label('No HP')
                                            ->tel(),

                                        Select::make('status_pernikahan')
                                            ->label('Status Pernikahan')
                                            ->options([
                                                'Single' => 'Single',
                                                'Menikah' => 'Menikah',
                                                'Cerai' => 'Cerai',
                                            ])
                                            ->native(false),

                                        TextInput::make('agama')
                                            ->label('Agama'),

                                        TextInput::make('kewarganegaraan')
                                            ->label('Kewarganegaraan'),

                                        Select::make('status_karyawan')
                                            ->label('Status Karyawan')
                                            ->options([
                                                'Tetap' => 'Tetap',
                                                'Kontrak' => 'Kontrak',
                                                'Magang' => 'Magang',
                                            ])
                                            ->native(false),

                                        DatePicker::make('tanggal_masuk')
                                            ->label('Tanggal Masuk')
                                            ->native(false),

                                        DatePicker::make('tanggal_keluar')
                                            ->label('Tanggal Keluar')
                                            ->native(false),

                                        TextInput::make('lokasi_kerja')
                                            ->label('Lokasi Kerja'),

                                        // Fix: pakai query bukan pluck langsung
                                        Select::make('atasan_id')
                                            ->label('Atasan Langsung')
                                            ->options(function () {
                                                return User::whereIn('level', [
                                                    Role::Superuser->value,
                                                    Role::Superadmin->value,
                                                ])
                                                    ->orderBy('name')
                                                    ->pluck('name', 'id');
                                            })
                                            ->searchable()
                                            ->preload()
                                            ->native(false),

                                        TextInput::make('barcode_signature')
                                            ->label('Barcode Signature'),

                                        FileUpload::make('foto')
                                            ->image()
                                            ->imageEditor()
                                            ->imageResizeMode('cover')
                                            ->imageCropAspectRatio('1:1')
                                            ->imageResizeTargetWidth('400')  // max 400px
                                            ->imageResizeTargetHeight('400')
                                            ->disk('public')
                                            ->directory('users')
                                            ->visibility('public')
                                            ->columnSpanFull(),

                                    ])
                                    ->columns(2),
                            ]),

                        /*
                        |------------------------------------------------------
                        | FINANCE
                        |------------------------------------------------------
                        */

                        Tab::make('Finance')
                            ->icon('heroicon-o-banknotes')
                            ->schema([
                                Section::make()
                                    ->relationship('finance')
                                    ->schema([

                                        TextInput::make('gaji_pokok')
                                            ->label('Gaji Pokok')
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->placeholder('0'),

                                        TextInput::make('tunjangan')
                                            ->label('Tunjangan')
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->placeholder('0'),

                                        TextInput::make('bank_name')
                                            ->label('Nama Bank'),

                                        TextInput::make('no_rekening')
                                            ->label('No. Rekening'),

                                        TextInput::make('npwp')
                                            ->label('NPWP'),

                                        TextInput::make('bpjs_kesehatan')
                                            ->label('BPJS Kesehatan'),

                                        TextInput::make('bpjs_ketenagakerjaan')
                                            ->label('BPJS Ketenagakerjaan'),

                                    ])
                                    ->columns(2),
                            ]),

                        /*
                        |------------------------------------------------------
                        | DOCUMENTS
                        |------------------------------------------------------
                        */

                        Tab::make('Documents')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Section::make()
                                    ->relationship('document')
                                    ->schema([

                                        FileUpload::make('ktp_file')
                                            ->label('KTP')
                                            ->disk('public')
                                            ->directory('documents/ktp')
                                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                                            ->downloadable(),

                                        FileUpload::make('kk_file')
                                            ->label('Kartu Keluarga')
                                            ->disk('public')
                                            ->directory('documents/kk')
                                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                                            ->downloadable(),

                                        FileUpload::make('cv_file')
                                            ->label('CV')
                                            ->disk('public')
                                            ->directory('documents/cv')
                                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                                            ->downloadable(),

                                        FileUpload::make('ijazah_file')
                                            ->label('Ijazah')
                                            ->disk('public')
                                            ->directory('documents/ijazah')
                                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                                            ->downloadable(),

                                        FileUpload::make('kontrak_file')
                                            ->label('Kontrak Kerja')
                                            ->disk('public')
                                            ->directory('documents/kontrak')
                                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                                            ->downloadable(),

                                    ])
                                    ->columns(2),
                            ]),

                        /*
                        |------------------------------------------------------
                        | DEPARTMENT
                        |------------------------------------------------------
                        */

                        Tab::make('Department')
                            ->icon('heroicon-o-building-office')
                            ->schema([

                                Select::make('departments')
                                    ->relationship('departments', 'nama_department')
                                    ->multiple()
                                    ->preload()
                                    ->searchable()
                                    ->native(false),

                            ]),

                    ])
                    ->columnSpanFull(),
            ]);
    }
}
