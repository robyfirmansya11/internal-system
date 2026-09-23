<?php

namespace App\Filament\Pages;

use App\Enums\Role;
use App\Filament\Pages\SuratPerintahBayarReport\Widgets\CompanyChart;
use App\Filament\Pages\SuratPerintahBayarReport\Widgets\DepartmentChart;
use App\Filament\Pages\SuratPerintahBayarReport\Widgets\MonthlyChart;
use App\Filament\Pages\SuratPerintahBayarReport\Widgets\StatusChart;
use App\Models\Company;
use App\Models\Department;
use App\Models\SuratPerintahBayar;
use App\Models\User;
use BackedEnum;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use UnitEnum;

class SuratPerintahBayarReport extends Page implements HasForms, Tables\Contracts\HasTable
{
    use InteractsWithForms;
    use Tables\Concerns\InteractsWithTable;

    protected string $view =
        'filament.pages.surat-perintah-bayar-report';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Report';

    protected static ?string $title = 'Payment Application Letter Report';

    protected static ?string $navigationLabel = 'Report Payment Application Letter';

    protected static ?int $navigationSort = 4;

    public ?array $filters = [];

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(6)
            ->components([
                Forms\Components\DatePicker::make('date_from')
                    ->label('Date From')
                    ->live(),

                Forms\Components\DatePicker::make('date_until')
                    ->label('Date Until')
                    ->live(),

                Select::make('month')
                    ->label('Month')
                    ->options(
                        collect(range(1, 12))
                            ->mapWithKeys(fn (int $month): array => [
                                $month => \Carbon\Carbon::create()->month($month)->translatedFormat('F'),
                            ])
                    )
                    ->live(),

                Select::make('year')
                    ->label('Year')
                    ->options(
                        collect(range(now()->year, now()->year - 10))
                            ->mapWithKeys(fn ($year) => [$year => $year])
                    )
                    ->default(now()->year)
                    ->live(),

                Select::make('company_id')
                    ->label('PT / Company')
                    ->options(
                        Company::query()
                            ->orderBy('nama')
                            ->get()
                            ->mapWithKeys(fn (Company $company): array => [
                                $company->id => trim("{$company->kode} - {$company->nama}", ' -'),
                            ])
                            ->all()
                    )
                    ->searchable()
                    ->preload()
                    ->live(),

                Select::make('department_id')
                    ->label('Department')
                    ->options(
                        Department::query()
                            ->pluck('nama_department', 'id')
                            ->toArray()
                    )
                    ->searchable()
                    ->preload()
                    ->live(),

            ])
            ->statePath('filters');
    }

    /*
    |--------------------------------------------------------------------------
    | PERMISSION
    |--------------------------------------------------------------------------
    */

    public static function canAccess(): bool
    {
        return in_array(
            auth()->user()->level,
            [
                Role::User,
                Role::Superuser,
                Role::Admin,
                Role::Superadmin,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | KPI
    |--------------------------------------------------------------------------
    */

    protected function getBaseQuery(): Builder
    {
        return SuratPerintahBayar::query()
            ->with(['user', 'company', 'department'])
            ->when(
                $this->filters['date_from'] ?? null,
                fn ($q, $date) => $q->whereDate('tanggal_penagihan', '>=', $date)
            )
            ->when(
                $this->filters['date_until'] ?? null,
                fn ($q, $date) => $q->whereDate('tanggal_penagihan', '<=', $date)
            )
            ->when(
                $this->filters['month'] ?? null,
                fn ($q, $month) => $q->whereMonth('tanggal_penagihan', $month)
            )
            ->when(
                $this->filters['year'] ?? null,
                fn ($q, $year) => $q->whereYear('tanggal_penagihan', $year)
            )
            ->when(
                $this->filters['company_id'] ?? null,
                fn ($q, $company) => $q->where('company_id', $company)
            )
            ->when(
                $this->filters['department_id'] ?? null,
                fn ($q, $dept) => $q->where('department_id', $dept)
            );
    }

    protected function getHeaderWidgets(): array
    {
        return [
            MonthlyChart::class,
            CompanyChart::class,
            StatusChart::class,
            DepartmentChart::class,
        ];
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
            ->defaultSort('created_at', 'desc')
            ->columns([

                Tables\Columns\TextColumn::make('index')
                    ->label('No.')
                    ->rowIndex(),

                Tables\Columns\TextColumn::make('no_invoice')
                    ->label('Invoice')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('company.nama')
                    ->label('Company')
                    ->sortable(),

                Tables\Columns\TextColumn::make('department.nama_department')
                    ->label('Department')
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Employee')
                    ->sortable(),

                Tables\Columns\TextColumn::make('tanggal_penagihan')
                    ->label('Billing Date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('tanggal_jatuhtempo')
                    ->label('Due Date')
                    ->date(),

                Tables\Columns\TextColumn::make('jumlah_total')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ]),

            ])

            ->filters([

                /*
                |--------------------------------------------------------------------------
                | COMPANY FILTER
                |--------------------------------------------------------------------------
                */

                Tables\Filters\SelectFilter::make('company_id')
                    ->label('PT / Company')
                    ->relationship('company', 'nama'),

                /*
                |--------------------------------------------------------------------------
                | USER FILTER
                |--------------------------------------------------------------------------
                */

                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Employee')
                    ->relationship('user', 'name'),

                /*
                |--------------------------------------------------------------------------
                | DEPARTMENT FILTER
                |--------------------------------------------------------------------------
                */

                Tables\Filters\SelectFilter::make('department_id')
                    ->label('Department')
                    ->relationship('department', 'nama_department'),

                /*
                |--------------------------------------------------------------------------
                | STATUS FILTER
                |--------------------------------------------------------------------------
                */

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'Pending Approval' => 'Pending',
                        'Approved' => 'Approved',
                        'Rejected' => 'Rejected',
                    ]),

                /*
                |--------------------------------------------------------------------------
                | YEAR FILTER
                |--------------------------------------------------------------------------
                */

                Tables\Filters\Filter::make('year')
                    ->form([
                        Forms\Components\Select::make('year')
                            ->label('Year')
                            ->options(
                                collect(range(now()->year, now()->year - 10))
                                    ->mapWithKeys(fn ($year) => [$year => $year])
                            ),
                    ])
                    ->query(function (Builder $query, array $data) {

                        if ($data['year']) {
                            $query->whereYear('tanggal_penagihan', $data['year']);
                        }

                    }),

                /*
                |--------------------------------------------------------------------------
                | DATE RANGE FILTER
                |--------------------------------------------------------------------------
                */

                Tables\Filters\Filter::make('tanggal_penagihan')
                    ->form([
                        Forms\Components\DatePicker::make('from'),

                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data) {

                        return $query
                            ->when(
                                $data['from'],
                                fn ($q) => $q->whereDate('tanggal_penagihan', '>=', $data['from'])
                            )
                            ->when(
                                $data['until'],
                                fn ($q) => $q->whereDate('tanggal_penagihan', '<=', $data['until'])
                            );

                    }),

            ])

            /*
            |--------------------------------------------------------------------------
            | EXPORT
            |--------------------------------------------------------------------------
            */
            ->headerActions([
                ExportAction::make()
                    ->exports([
                        ExcelExport::make()
                            ->withColumns([
                                Column::make('no_invoice')
                                    ->heading('Invoice'),

                                Column::make('company.nama')
                                    ->heading('Company'),

                                Column::make('department.nama_department')
                                    ->heading('Department'),

                                Column::make('user.name')
                                    ->heading('Employee'),

                                Column::make('tanggal_penagihan')
                                    ->heading('Billing Date'),

                                Column::make('tanggal_jatuhtempo')
                                    ->heading('Due Date'),

                                Column::make('jumlah_total')
                                    ->heading('Total'),

                                Column::make('status')
                                    ->heading('Status'),

                                Column::make('created_at')
                                    ->heading('Created At'),
                            ]),
                    ]),
            ]);
    }

    protected function getStats(): array
    {
        $query = $this->getBaseQuery();

        return [
            'total' => (clone $query)->sum('jumlah_total'),
            'approved' => (clone $query)->where('status', 'Approved')->sum('jumlah_total'),
            'pending' => (clone $query)->where('status', 'Pending Approval')->sum('jumlah_total'),
            'rejected' => (clone $query)->where('status', 'Rejected')->sum('jumlah_total'),
            'count' => (clone $query)->count(),
        ];
    }
    /*
    |--------------------------------------------------------------------------
    | SUMMARY TOTALS
    |--------------------------------------------------------------------------
    */

    public function getSummary(): array
    {
        $query = SuratPerintahBayar::query();

        return [

            'grand_total' => $query->sum('jumlah_total'),

            'approved_total' => $query->where('status', 'approved')->sum('jumlah_total'),

            'pending_total' => $query->where('status', 'pending')->sum('jumlah_total'),

            'rejected_total' => $query->where('status', 'rejected')->sum('jumlah_total'),

        ];
    }
}
