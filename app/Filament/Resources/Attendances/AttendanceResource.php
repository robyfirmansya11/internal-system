<?php

namespace App\Filament\Resources\Attendances;

use App\Filament\Resources\Attendances\Pages\EditAttendance;
use App\Filament\Resources\Attendances\Pages\ListAttendances;
use App\Models\Attendance;
use App\Models\Department;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static ?string $navigationLabel = 'Attendance Records';

    protected static ?string $modelLabel = 'Attendance';

    protected static ?string $pluralModelLabel = 'Attendance Records';

    protected static string|UnitEnum|null $navigationGroup = 'HRIS';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFingerPrint;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([

                Select::make('status')
                    ->label('Status')
                    ->options([
                        'present' => 'Present',
                        'late' => 'Late',
                        'absent' => 'Absent',
                    ])
                    ->required(),

                DateTimePicker::make('clock_in')
                    ->label('Clock In')
                    ->seconds(false),

                DateTimePicker::make('clock_out')
                    ->label('Clock Out')
                    ->seconds(false),

                Textarea::make('note')
                    ->label('Catatan')
                    ->columnSpanFull(),

            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('date', 'desc')

            ->headerActions([

                Action::make('export_excel')
                    ->label('Export Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->form([
                        Select::make('month')
                            ->label('Bulan')
                            ->options([
                                1 => 'Januari',
                                2 => 'Februari',
                                3 => 'Maret',
                                4 => 'April',
                                5 => 'Mei',
                                6 => 'Juni',
                                7 => 'Juli',
                                8 => 'Agustus',
                                9 => 'September',
                                10 => 'Oktober',
                                11 => 'November',
                                12 => 'Desember',
                            ])
                            ->default(now()->month)
                            ->required()
                            ->native(false),

                        Select::make('year')
                            ->label('Tahun')
                            ->options(
                                collect(range(now()->year, now()->year - 3))
                                    ->mapWithKeys(fn ($y) => [$y => $y])
                            )
                            ->default(now()->year)
                            ->required()
                            ->native(false),

                        Select::make('department_id')
                            ->label('Department')
                            ->options(
                                Department::orderBy('nama_department')
                                    ->pluck('nama_department', 'id')
                            )
                            ->placeholder('Semua Department')
                            ->searchable()
                            ->native(false),

                        Select::make('user_id')
                            ->label('Karyawan')
                            ->options(
                                \App\Models\User::orderBy('name')
                                    ->pluck('name', 'id')
                            )
                            ->placeholder('Semua Karyawan')
                            ->searchable()
                            ->native(false),
                    ])

                    ->action(function (array $data, \Livewire\Component $livewire) {
                        $month = (int) $data['month'];
                        $year = (int) $data['year'];
                        $userId = isset($data['user_id']) ? (int) $data['user_id'] : null;
                        $departmentId = isset($data['department_id']) ? (int) $data['department_id'] : null;

                        $monthName = [
                            1 => 'Januari',   2 => 'Februari',
                            3 => 'Maret',     4 => 'April',
                            5 => 'Mei',       6 => 'Juni',
                            7 => 'Juli',      8 => 'Agustus',
                            9 => 'September', 10 => 'Oktober',
                            11 => 'November',  12 => 'Desember',
                        ][$month];

                        $filename = "Rekap-Absensi-{$monthName}-{$year}.xlsx";

                        $url = route('attendance.export', [
                            'month' => $month,
                            'year' => $year,
                            'user_id' => $userId,
                            'department_id' => $departmentId,
                            'filename' => $filename,
                        ]);

                        $livewire->js("window.open('{$url}', '_blank')");
                    }),

            ])

            ->columns([

                TextColumn::make('index')
                    ->label('No.')
                    ->rowIndex(),

                TextColumn::make('user.name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('date')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),

                ImageColumn::make('clock_in_photo')
                    ->label('Clock In Photo')
                    ->disk('public')
                    ->circular()
                    ->size(40),

                TextColumn::make('clock_in')
                    ->label('Clock In')
                    ->time('H:i')
                    ->placeholder('-'),

                TextColumn::make('clock_out')
                    ->label('Clock Out')
                    ->time('H:i')
                    ->placeholder('-'),

                TextColumn::make('clock_in_address')
                    ->label('Clock In Address')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->clock_in_address)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('note')
                    ->label('Note')
                    ->placeholder('-')
                    ->wrap()
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'present' => 'success',
                        'late' => 'warning',
                        'absent' => 'danger',
                        default => 'gray',
                    }),

            ])

            ->filters([

                SelectFilter::make('status')
                    ->options([
                        'present' => 'Present',
                        'late' => 'Late',
                        'absent' => 'Absent',
                    ]),

                SelectFilter::make('user_id')
                    ->label('Karyawan')
                    ->relationship('user', 'name')
                    ->searchable(),

                Filter::make('date')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label('Dari Tanggal'),
                        \Filament\Forms\Components\DatePicker::make('until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when(
                                $data['from'],
                                fn ($q) => $q->whereDate('date', '>=', $data['from'])
                            )
                            ->when(
                                $data['until'],
                                fn ($q) => $q->whereDate('date', '<=', $data['until'])
                            );
                    }),

            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | PERMISSIONS — hanya HRD & Superadmin (IT)
    |--------------------------------------------------------------------------
    */

    public static function canViewAny(): bool
    {
        return self::userHasAccess();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return self::userHasAccess();
    }

    public static function canDelete($record): bool
    {
        return self::userHasAccess();
    }

    protected static function userHasAccess(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return $user->isSuperadmin() || $user->isHRD();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAttendances::route('/'),
            'edit' => EditAttendance::route('/{record}/edit'),
        ];
    }
}
