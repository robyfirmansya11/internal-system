<?php

namespace App\Filament\Pages;

use App\Enums\Role;
use App\Models\User;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Saade\FilamentAutograph\Forms\Components\SignaturePad;

class MyProfile extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected string $view = 'filament.pages.my-profile';

    protected static ?string $navigationLabel = 'My Profile';

    protected static ?string $title = 'My Profile';

    protected static string|\UnitEnum|null $navigationGroup = 'Account';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-circle';

    public ?array $data = [];

    public User $user;

    /*
    |--------------------------------------------------------------------------
    | ROLE CHECK
    |--------------------------------------------------------------------------
    */
    protected function canEditHrFields(): bool
    {
        return in_array(
            auth()->user()?->level,
            [
                Role::Admin,
                Role::Superadmin,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | MOUNT
    |--------------------------------------------------------------------------
    */
    public function mount(): void
    {
        $this->user = auth()->user()->load([
            'profile',
            'finance',
            'document',
        ]);

        $this->form->fill([
            /*
            |------------------------------------------------------------------
            | USERS
            |------------------------------------------------------------------
            */
            'name' => $this->user->name,
            'email' => $this->user->email,
            'jabatan' => $this->user->jabatan,
            'level' => $this->user->level?->value,

            /*
            |------------------------------------------------------------------
            | PROFILE
            |------------------------------------------------------------------
            */
            'foto' => $this->user->profile?->foto ?? $this->user->foto,
            'nik' => $this->user->profile?->nik,
            'employee_id' => $this->user->profile?->employee_id,
            'status_karyawan' => $this->user->profile?->status_karyawan,
            'tanggal_masuk' => $this->user->profile?->tanggal_masuk,
            'tanggal_keluar' => $this->user->profile?->tanggal_keluar,
            'lokasi_kerja' => $this->user->profile?->lokasi_kerja,
            'barcode_signature' => $this->user->profile?->barcode_signature,
            'kewarganegaraan' => $this->user->profile?->kewarganegaraan,
            'tempat_lahir' => $this->user->profile?->tempat_lahir,
            'tanggal_lahir' => $this->user->profile?->tanggal_lahir,
            'jenis_kelamin' => $this->user->profile?->jenis_kelamin,
            'alamat' => $this->user->profile?->alamat,
            'no_hp' => $this->user->profile?->no_hp,
            'agama' => $this->user->profile?->agama,
            'status_pernikahan' => $this->user->profile?->status_pernikahan,

            /*
            |------------------------------------------------------------------
            | FINANCE
            |------------------------------------------------------------------
            */
            'gaji_pokok' => $this->user->finance?->gaji_pokok,
            'tunjangan' => $this->user->finance?->tunjangan,
            'bank_name' => $this->user->finance?->bank_name,
            'no_rekening' => $this->user->finance?->no_rekening,
            'npwp' => $this->user->finance?->npwp,
            'bpjs_kesehatan' => $this->user->finance?->bpjs_kesehatan,
            'bpjs_ketenagakerjaan' => $this->user->finance?->bpjs_ketenagakerjaan,

            /*
            |------------------------------------------------------------------
            | DOCUMENTS
            |------------------------------------------------------------------
            */
            'kontrak_file' => $this->user->document?->kontrak_file,
            'ktp_file' => $this->user->document?->ktp_file,
            'kk_file' => $this->user->document?->kk_file,
            'cv_file' => $this->user->document?->cv_file,
            'ijazah_file' => $this->user->document?->ijazah_file,

            /*
            |------------------------------------------------------------------
            | PASSWORD
            |------------------------------------------------------------------
            */
            'password' => null,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | FORM
    |--------------------------------------------------------------------------
    */
    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Tabs::make('ProfileTabs')
                    ->tabs([

                        /*
                        |------------------------------------------------------
                        | ACCOUNT
                        |------------------------------------------------------
                        */
                        Tab::make('Account')
                            ->icon('heroicon-o-user')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Full Name')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('email')
                                    ->email()
                                    ->required()
                                    ->unique(
                                        table: User::class,
                                        column: 'email',
                                        ignorable: $this->user,
                                    ),

                                Forms\Components\Select::make('jabatan')
                                    ->label('Position')
                                    ->options([
                                        'Staff' => 'Staff',
                                        'Manager' => 'Manager',
                                        'Finance Manager' => 'Finance Manager',
                                        'HRD' => 'HRD',
                                        'Vice President' => 'Vice President',
                                        'President Director' => 'President Director',
                                    ])
                                    ->native(false)
                                    ->disabled(fn () => ! $this->canEditHrFields()),

                                Forms\Components\Select::make('level')
                                    ->label('Role')
                                    ->options(
                                        collect(Role::cases())
                                            ->mapWithKeys(fn (Role $r) => [
                                                $r->value => $r->label(),
                                            ])
                                    )
                                    ->native(false)
                                    ->disabled(fn () => ! $this->canEditHrFields()),

                                Forms\Components\TextInput::make('password')
                                    ->password()
                                    ->autocomplete('new-password')
                                    ->minLength(8)
                                    ->revealable()
                                    ->dehydrated(false)
                                    ->helperText('Leave this field blank to keep the current password.')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),

                        /*
                        |------------------------------------------------------
                        | PROFILE
                        |------------------------------------------------------
                        */
                        Tab::make('Profile')
                            ->icon('heroicon-o-identification')
                            ->schema([
                                Forms\Components\FileUpload::make('foto')
                                    ->label('Profile Photo')
                                    ->image()
                                    ->disk('public')
                                    ->avatar()
                                    ->imageEditor()
                                    ->imageResizeMode('cover')
                                    ->imageCropAspectRatio('1:1')
                                    ->imageResizeTargetWidth('400')
                                    ->imageResizeTargetHeight('400')
                                    ->directory('users')
                                    ->visibility('public')
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('nik')
                                    ->label('NIK')
                                    ->disabled(fn () => ! $this->canEditHrFields()),

                                Forms\Components\TextInput::make('employee_id')
                                    ->label('Employee ID')
                                    ->disabled(fn () => ! $this->canEditHrFields()),

                                Forms\Components\TextInput::make('tempat_lahir')
                                    ->label('Place of Birth'),

                                Forms\Components\DatePicker::make('tanggal_lahir')
                                    ->label('Date of Birth')
                                    ->native(false),

                                Forms\Components\Select::make('jenis_kelamin')
                                    ->label('Gender')
                                    ->options([
                                        'Male' => 'Male',
                                        'Female' => 'Female',
                                    ])
                                    ->native(false),

                                Forms\Components\Textarea::make('alamat')
                                    ->label('Address')
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('no_hp')
                                    ->label('Phone Number')
                                    ->tel()
                                    ->maxLength(20),

                                Forms\Components\Select::make('agama')
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

                                Forms\Components\TextInput::make('kewarganegaraan')
                                    ->label('Nationality'),

                                Forms\Components\Select::make('status_pernikahan')
                                    ->label('Marital Status')
                                    ->options([
                                        'Single' => 'Single',
                                        'Married' => 'Married',
                                        'Divorced' => 'Divorced',
                                    ])
                                    ->native(false),

                                Forms\Components\Select::make('status_karyawan')
                                    ->label('Employee Status')
                                    ->options([
                                        'Permanent' => 'Permanent',
                                        'Contract' => 'Contract',
                                        'Intern' => 'Intern',
                                    ])
                                    ->native(false)
                                    ->disabled(fn () => ! $this->canEditHrFields()),

                                Forms\Components\DatePicker::make('tanggal_masuk')
                                    ->label('Hire Date')
                                    ->native(false)
                                    ->disabled(fn () => ! $this->canEditHrFields()),

                                Forms\Components\DatePicker::make('tanggal_keluar')
                                    ->label('Resignation Date')
                                    ->native(false)
                                    ->disabled(fn () => ! $this->canEditHrFields()),

                                Forms\Components\Select::make('lokasi_kerja')
                                    ->label('Work Location')
                                    ->options([
                                        'Head Office' => 'Head Office',
                                        'Site Office' => 'Site Office',
                                    ])
                                    ->native(false)
                                    ->disabled(fn () => ! $this->canEditHrFields()),

                                SignaturePad::make('barcode_signature')
                                    ->label('Signature')

                                    ->columnSpanFull(),
                            ])
                            ->columns(2),

                        /*
                        |------------------------------------------------------
                        | DOCUMENTS
                        |------------------------------------------------------
                        */
                        Tab::make('Documents')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Forms\Components\FileUpload::make('kontrak_file')
                                    ->label('Employment Contract')
                                    ->disk('private')
                                    ->directory('documents/kontrak')
                                    ->acceptedFileTypes(['application/pdf', 'image/*'])
                                    ->maxSize(2048)
                                    ->downloadable()
                                    ->openable()
                                    ->disabled(fn () => ! $this->canEditHrFields()),

                                Forms\Components\FileUpload::make('ktp_file')
                                    ->label('KTP')
                                    ->disk('private')
                                    ->directory('documents/ktp')
                                    ->acceptedFileTypes(['application/pdf', 'image/*'])
                                    ->maxSize(2048)
                                    ->openable()
                                    ->downloadable(),

                                Forms\Components\FileUpload::make('kk_file')
                                    ->label('Family Card')
                                    ->disk('private')
                                    ->directory('documents/kk')
                                    ->acceptedFileTypes(['application/pdf', 'image/*'])
                                    ->maxSize(2048)
                                    ->openable()
                                    ->downloadable(),

                                Forms\Components\FileUpload::make('cv_file')
                                    ->label('CV')
                                    ->disk('private')
                                    ->directory('documents/cv')
                                    ->acceptedFileTypes(['application/pdf', 'image/*'])
                                    ->maxSize(2048)
                                    ->openable()
                                    ->downloadable(),

                                Forms\Components\FileUpload::make('ijazah_file')
                                    ->label('Diploma / Degree Certificate')
                                    ->disk('private')
                                    ->directory('documents/ijazah')
                                    ->acceptedFileTypes(['application/pdf', 'image/*'])
                                    ->maxSize(2048)
                                    ->openable()
                                    ->downloadable(),
                            ])
                            ->columns(2),

                        /*
                        |------------------------------------------------------
                        | FINANCE
                        |------------------------------------------------------
                        */
                        Tab::make('Finance')
                            ->icon('heroicon-o-banknotes')
                            ->schema([
                                Forms\Components\TextInput::make('gaji_pokok')
                                    ->label('Basic Salary')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->placeholder('0')
                                    ->inputMode('decimal')
                                    ->disabled(fn () => ! $this->canEditHrFields()),

                                Forms\Components\TextInput::make('tunjangan')
                                    ->label('Allowance')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->placeholder('0')
                                    ->inputMode('decimal')
                                    ->disabled(fn () => ! $this->canEditHrFields()),

                                Forms\Components\TextInput::make('bank_name')
                                    ->label('Bank Name')
                                    ->disabled(fn () => ! $this->canEditHrFields()),

                                Forms\Components\TextInput::make('no_rekening')
                                    ->label('Bank Account Number')
                                    ->disabled(fn () => ! $this->canEditHrFields()),

                                Forms\Components\TextInput::make('npwp')
                                    ->label('NPWP')
                                    ->disabled(fn () => ! $this->canEditHrFields()),

                                Forms\Components\TextInput::make('bpjs_kesehatan')
                                    ->label('Health Insurance (BPJS)')
                                    ->disabled(fn () => ! $this->canEditHrFields()),

                                Forms\Components\TextInput::make('bpjs_ketenagakerjaan')
                                    ->label('Employment Insurance (BPJS)')
                                    ->disabled(fn () => ! $this->canEditHrFields()),
                            ])
                            ->columns(2),

                    ])
                    ->columnSpanFull(),
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | SAVE
    |--------------------------------------------------------------------------
    */
    public function save(): void
    {
        $data = $this->form->getState();

        /*
        |----------------------------------------------------------------------
        | USERS
        |----------------------------------------------------------------------
        */
        $userData = [
            'name' => $data['name'],
            'email' => $data['email'],
        ];

        if ($this->canEditHrFields()) {
            $userData['jabatan'] = $data['jabatan'] ?? null;
            $userData['level'] = isset($data['level']) ? Role::from($data['level']) : null;
        }

        $this->user->update($userData);

        /*
        |----------------------------------------------------------------------
        | PASSWORD
        |----------------------------------------------------------------------
        */
        if (! empty($data['password'])) {
            $this->user->update([
                'password' => $data['password'],
            ]);
        }

        /*
        |----------------------------------------------------------------------
        | PROFILE
        |----------------------------------------------------------------------
        */
        $profileData = [
            'foto' => $data['foto'] ?? null,
            'tempat_lahir' => $data['tempat_lahir'] ?? null,
            'tanggal_lahir' => $data['tanggal_lahir'] ?? null,
            'jenis_kelamin' => $data['jenis_kelamin'] ?? null,
            'alamat' => $data['alamat'] ?? null,
            'no_hp' => $data['no_hp'] ?? null,
            'agama' => $data['agama'] ?? null,
            'status_pernikahan' => $data['status_pernikahan'] ?? null,
            'kewarganegaraan' => $data['kewarganegaraan'] ?? null,
            'barcode_signature' => $data['barcode_signature'] ?? null,
        ];

        if ($this->canEditHrFields()) {
            $profileData = array_merge($profileData, [
                'nik' => $data['nik'] ?? null,
                'employee_id' => $data['employee_id'] ?? null,
                'status_karyawan' => $data['status_karyawan'] ?? null,
                'tanggal_masuk' => $data['tanggal_masuk'] ?? null,
                'tanggal_keluar' => $data['tanggal_keluar'] ?? null,
                'lokasi_kerja' => $data['lokasi_kerja'] ?? null,
                'barcode_signature' => $data['barcode_signature'] ?? null,
            ]);
        }

        $this->user->profile()->updateOrCreate(
            ['user_id' => $this->user->id],
            $profileData
        );

        /*
        |----------------------------------------------------------------------
        | DOCUMENTS
        |----------------------------------------------------------------------
        */
        $documentData = [
            'ktp_file' => $data['ktp_file'] ?? null,
            'kk_file' => $data['kk_file'] ?? null,
            'cv_file' => $data['cv_file'] ?? null,
            'ijazah_file' => $data['ijazah_file'] ?? null,
        ];

        if ($this->canEditHrFields()) {
            $documentData['kontrak_file'] = $data['kontrak_file'] ?? null;
        }

        $this->user->document()->updateOrCreate(
            ['user_id' => $this->user->id],
            $documentData
        );

        /*
        |----------------------------------------------------------------------
        | FINANCE
        |----------------------------------------------------------------------
        */
        if ($this->canEditHrFields()) {
            $this->user->finance()->updateOrCreate(
                ['user_id' => $this->user->id],
                [
                    'gaji_pokok' => $data['gaji_pokok'] ?? null,
                    'tunjangan' => $data['tunjangan'] ?? null,
                    'bank_name' => $data['bank_name'] ?? null,
                    'no_rekening' => $data['no_rekening'] ?? null,
                    'npwp' => $data['npwp'] ?? null,
                    'bpjs_kesehatan' => $data['bpjs_kesehatan'] ?? null,
                    'bpjs_ketenagakerjaan' => $data['bpjs_ketenagakerjaan'] ?? null,
                ]
            );
        }

        Notification::make()
            ->title('Profile updated successfully')
            ->success()
            ->send();
    }
}
