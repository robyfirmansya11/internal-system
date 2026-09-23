<?php

namespace App\Filament\Pages;

use App\Enums\Role;
use App\Filament\Widgets\OvertimeEmployeeChart;
use App\Services\OvertimeReportQueryService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class OvertimeReport extends Page implements HasForms, Tables\Contracts\HasTable
{
    use InteractsWithForms;
    use Tables\Concerns\InteractsWithTable;

    protected string $view = 'filament.pages.overtime.overtime-report';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Report';

    protected static ?string $title = 'Overtime Report';

    protected static ?string $navigationLabel = 'Overtime Report';

    protected static ?int $navigationSort = 4;

    /*
    |--------------------------------------------------------------------------
    | FILTER STATE
    |--------------------------------------------------------------------------
    */

    public ?array $filters = [];

    /*
    |--------------------------------------------------------------------------
    | FORM FILTER (SAMA SEPERTI SPB)
    |--------------------------------------------------------------------------
    */
    public function mount(): void
    {
        $user = auth()->user();

        $this->form->fill([
            'year' => now()->year,
            'month' => now()->month,
            'user_id' => $user->isUser() ? $user->id : null,   // ⬅️ TAMBAHAN
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $user = auth()->user();

        return $schema
            ->components([

                Grid::make(4)
                    ->schema([

                        Select::make('year')
                            ->label('Year')
                            ->options(
                                collect(range(now()->year, now()->year - 5))
                                    ->mapWithKeys(fn ($year) => [$year => $year])
                            )
                            ->default(now()->year)
                            ->live(),

                        Select::make('month')
                            ->label('Month')
                            ->options([
                                1 => 'January', 2 => 'February', 3 => 'March',
                                4 => 'April', 5 => 'May', 6 => 'June',
                                7 => 'July', 8 => 'August', 9 => 'September',
                                10 => 'October', 11 => 'November', 12 => 'December',
                            ])
                            ->default(now()->month)
                            ->live(),

                        Select::make('user_id')
                            ->label('Employee')
                            ->options(fn () => app(OvertimeReportQueryService::class)
                                ->accessibleEmployees($user)
                                ->pluck('name', 'id'))
                            ->default(fn () => $user->isUser() ? $user->id : null)
                            ->disabled(fn () => $user->isUser())
                            ->dehydrated()
                            ->searchable()
                            ->preload()
                            ->visible(fn () => in_array($user->level, [
                                Role::Admin,
                                Role::Superadmin,
                                Role::Superuser,
                                Role::User,
                            ]))
                            ->live(),

                        Select::make('department_id')
                            ->label('Department')
                            ->options(fn () => app(OvertimeReportQueryService::class)
                                ->accessibleDepartments($user)
                                ->pluck('nama_department', 'id'))
                            ->searchable()
                            ->preload()
                            ->visible(fn () => in_array($user->level, [
                                Role::Admin,
                                Role::Superadmin,
                                Role::Superuser,
                            ])
                            )
                            ->live(),

                    ]),

            ])
            ->statePath('filters');
    }

    /*
    |--------------------------------------------------------------------------
    | BASE QUERY (SAMA POLA SPB)
    |--------------------------------------------------------------------------
    */

    protected function getBaseQuery(): Builder
    {
        return app(OvertimeReportQueryService::class)
            ->build(auth()->user(), $this->filters ?? []);
    }

    /*
    |--------------------------------------------------------------------------
    | TABLE
    |--------------------------------------------------------------------------
    */

    public function table(Table $table): Table
    {
        return $table

            ->query(fn () => $this->getBaseQuery())

            ->columns([

                Tables\Columns\TextColumn::make('no')
                    ->label('No.')
                    ->state(fn ($record, $livewire, $rowLoop) => $rowLoop?->iteration)
                    ->visible(fn () => ! app()->runningInConsole()),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Employee')
                    ->visible(fn () => auth()->user()->level !== Role::User
                    ),

                Tables\Columns\TextColumn::make('department.nama_department')
                    ->label('Department')
                    ->sortable(),

                Tables\Columns\TextColumn::make('tanggal_lembur')
                    ->label('Date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('mulai_lembur')
                    ->label('Start')
                    ->time('H:i'),

                Tables\Columns\TextColumn::make('selesai_lembur')
                    ->label('End')
                    ->time('H:i'),

                Tables\Columns\TextColumn::make('jumlah_jam_lembur')
                    ->label('Hours')
                    ->suffix(' hours')
                    ->sortable(),

                Tables\Columns\TextColumn::make('uraian_pekerjaan')
                    ->label('Description')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->uraian_pekerjaan),

            ])
            ->defaultSort('tanggal_lembur', 'desc')

            /*
            |--------------------------------------------------------------------------
            | EXPORT (SAMA POLA SPB)
            |--------------------------------------------------------------------------
            */

            ->headerActions([
                // ========================
                // EXPORT PDF (TAMBAHAN)
                // ========================
                Action::make('export_pdf')
                    ->label('Export PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('danger')
                    ->url(fn () => route('overtime.report.pdf', [
                        'year' => $this->filters['year'] ?? now()->year,
                        'month' => $this->filters['month'] ?? now()->month,
                        'user_id' => $this->filters['user_id'] ?? null,
                        'department_id' => $this->filters['department_id'] ?? null,
                    ]))
                    ->openUrlInNewTab(),

                Action::make('export_excel')
                    ->label('Export Excel')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->url(fn () => route('overtime.report.excel', [
                        'year' => $this->filters['year'] ?? now()->year,
                        'month' => $this->filters['month'] ?? now()->month,
                        'user_id' => $this->filters['user_id'] ?? null,
                        'department_id' => $this->filters['department_id'] ?? null,
                    ])),

            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | SUMMARY
    |--------------------------------------------------------------------------
    */

    protected function getStats(): array
    {
        $query = $this->getBaseQuery();

        $stats = [
            'total_hours' => (clone $query)->sum('jumlah_jam_lembur'),
            'total_records' => (clone $query)->count(),
        ];

        // hanya admin lihat jumlah employee
        if (auth()->user()->level !== Role::User) {

            $stats['employees'] = (clone $query)
                ->distinct('user_id')
                ->count('user_id');

        }

        return $stats;
    }

    // protected function getHeaderWidgets(): array
    // {
    //     if (auth()->user()->level === Role::User) {
    //         return [];
    //     }

    //     return [
    //         OvertimeEmployeeChart::class,
    //     ];
    // }

}
