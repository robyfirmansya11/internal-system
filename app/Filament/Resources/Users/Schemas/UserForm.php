<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\Role;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Saade\FilamentAutograph\Forms\Components\SignaturePad;

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
                                        'Finance Manager' => 'Finance Manager',
                                        'HRD' => 'HRD',
                                        'Vice President' => 'Vice President',
                                        'President Director' => 'President Director',
                                    ])
                                    ->required()
                                    ->native(false),

                                Group::make()
                                    ->relationship('profile')
                                    ->schema([
                                        Select::make('atasan_id')
                                            ->label('Manager')
                                            ->options(function () {
                                                return User::whereIn('level', [
                                                    Role::Superuser->value,
                                                ])
                                                    ->orderBy('name')
                                                    ->pluck('name', 'id');
                                            })
                                            ->searchable()
                                            ->preload()
                                            ->native(false),
                                    ]),

                            ])
                            ->columns(2),

                        /*
                        |------------------------------------------------------
                        | EMPLOYEE PROFILE
                        |------------------------------------------------------
                        */

                        Tab::make('Profile')
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
                                            ->label('Place of Birth'),

                                        DatePicker::make('tanggal_lahir')
                                            ->label('Date of Birth')
                                            ->native(false),

                                        Select::make('jenis_kelamin')
                                            ->label('Gender')
                                            ->options([
                                                'Male' => 'Male',
                                                'Female' => 'Female',
                                            ])
                                            ->native(false),

                                        Textarea::make('alamat')
                                            ->label('Address')
                                            ->columnSpanFull(),

                                        TextInput::make('no_hp')
                                            ->label('Phone Number')
                                            ->tel(),

                                        Select::make('status_pernikahan')
                                            ->label('Marital Status')
                                            ->options([
                                                'Single' => 'Single',
                                                'Married' => 'Married',
                                                'Divorced' => 'Divorced',
                                            ])
                                            ->native(false),

                                        Select::make('agama')
                                            ->label('Religion')
                                            ->options([
                                                'Islam' => 'Islam',
                                                'Kristen' => 'Kristen',
                                                'Katolik' => 'Katolik',
                                                'Hindu' => 'Hindu',
                                                'Buddha' => 'Buddha',
                                                'Konghucu' => 'Konghucu',
                                            ])
                                            ->native(false),

                                        TextInput::make('kewarganegaraan')
                                            ->label('Nationality'),

                                        Select::make('status_karyawan')
                                            ->label('Employee Status')
                                            ->options([
                                                'Permanent' => 'Permanent',
                                                'Contract' => 'Contract',
                                                'Intern' => 'Intern',
                                            ])
                                            ->native(false),

                                        DatePicker::make('tanggal_masuk')
                                            ->label('Hire Date')
                                            ->native(false),

                                        DatePicker::make('tanggal_keluar')
                                            ->label('Resignation Date')
                                            ->native(false),

                                        Select::make('lokasi_kerja')
                                            ->label('Work Location')
                                            ->options([
                                                'Head Office' => 'Head Office',
                                                'Site Office' => 'Site Office',
                                            ])
                                            ->native(false),

                                        FileUpload::make('foto')
                                            ->label('Profile Photo')
                                            ->image()
                                            ->imageEditor()
                                            ->imageResizeMode('cover')
                                            ->imageCropAspectRatio('1:1')
                                            ->imageResizeTargetWidth('400')
                                            ->imageResizeTargetHeight('400')
                                            ->imagePreviewHeight('120')
                                            ->disk('public')
                                            ->directory('users')
                                            ->visibility('public'),

                                        SignaturePad::make('barcode_signature')
                                            ->label('Signature')
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
                                            ->label('Basic Salary')
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->placeholder('0'),

                                        TextInput::make('tunjangan')
                                            ->label('Allowance')
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->placeholder('0'),

                                        TextInput::make('bank_name')
                                            ->label('Bank Name'),

                                        TextInput::make('no_rekening')
                                            ->label('Account Number')
                                            ->placeholder('0'),

                                        TextInput::make('npwp')
                                            ->label('NPWP'),

                                        TextInput::make('bpjs_kesehatan')
                                            ->label('Health Insurance (BPJS)'),

                                        TextInput::make('bpjs_ketenagakerjaan')
                                            ->label('Employment Insurance (BPJS)'),

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
                                            ->label('Family Card')
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
                                            ->label('Diploma / Degree Certificate')
                                            ->disk('public')
                                            ->directory('documents/ijazah')
                                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                                            ->downloadable(),

                                        FileUpload::make('kontrak_file')
                                            ->label('Employment Contract')
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
