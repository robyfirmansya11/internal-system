<?php

namespace App\Filament\Pages;

use App\Enums\Role;
use App\Models\Company;
use App\Models\Department;
use App\Services\FinanceSummaryService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class FinanceSummary extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.finance-summary';
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;
    protected static string|\UnitEnum|null $navigationGroup = 'Report';
    protected static ?string $navigationLabel = 'Finance Summary';
    protected static ?string $title = 'Finance Summary';
    protected static ?int $navigationSort = 1;
    public ?array $filters = [];

    public function mount(): void
    {
        $this->form->fill(['date_from' => now()->startOfMonth()->toDateString(), 'date_until' => now()->endOfMonth()->toDateString()]);
    }

    public static function canAccess(): bool
    {
        return in_array(auth()->user()?->level, [Role::Admin, Role::Superadmin, Role::Superuser], true);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->columns(4)->components([
            Forms\Components\DatePicker::make('date_from')->label('Date From')->live(),
            Forms\Components\DatePicker::make('date_until')->label('Date Until')->live(),
            Forms\Components\Select::make('company_id')->label('PT / Company')->options(Company::query()->orderBy('nama')->pluck('nama', 'id'))->searchable()->live(),
            Forms\Components\Select::make('department_id')->label('Department')->options(Department::query()->orderBy('nama_department')->pluck('nama_department', 'id'))->searchable()->live(),
        ])->statePath('filters');
    }

    public function entries(): \Illuminate\Support\Collection { return app(FinanceSummaryService::class)->entries($this->filters ?? []); }
    public function summary(): array { return app(FinanceSummaryService::class)->summary($this->filters ?? []); }
}
