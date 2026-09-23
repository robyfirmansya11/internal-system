<?php

namespace App\Filament\Resources\NotaPenggantianBiayas;

use App\Filament\Resources\NotaPenggantianBiayas\Pages\CreateNotaPenggantianBiaya;
use App\Filament\Resources\NotaPenggantianBiayas\Pages\EditNotaPenggantianBiaya;
use App\Filament\Resources\NotaPenggantianBiayas\Pages\ListNotaPenggantianBiayas;
use App\Filament\Resources\NotaPenggantianBiayas\Schemas\NotaPenggantianBiayaForm;
use App\Filament\Resources\NotaPenggantianBiayas\Tables\NotaPenggantianBiayasTable;
use App\Models\NotaPenggantianBiaya;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class NotaPenggantianBiayaResource extends Resource
{
    protected static ?string $model = NotaPenggantianBiaya::class;
    protected static ?int $navigationSort = 4;
    protected static ?string $navigationLabel = 'Expense Reimbursement Note';
    protected static ?string $modelLabel = 'Expense Reimbursement Note';
    protected static ?string $pluralModelLabel = 'Expense Reimbursement Notes';
    protected static string|UnitEnum|null $navigationGroup = 'Finance, Accounting & Tax';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::Banknotes;

    public static function form(Schema $schema): Schema { return NotaPenggantianBiayaForm::configure($schema); }
    public static function table(Table $table): Table { return NotaPenggantianBiayasTable::configure($table); }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $query = parent::getEloquentQuery()->with(['user', 'company', 'department', 'details']);
        $fatDepartments = ['FAT', 'Finance, Accounting dan Tax', 'Finance, Accounting & Tax', 'Finance Accounting Tax', 'FAT Department'];
        $isFat = in_array($user->departments->first()?->nama_department, $fatDepartments, true);

        return ($isFat || $user->isSuperuser() || $user->isAdmin() || $user->isSuperadmin())
            ? $query
            : $query->where('user_id', $user->id);
    }

    public static function canViewAny(): bool { return true; }
    public static function canCreate(): bool { return true; }
    public static function canEdit($record): bool { return $record->canBeEditedBy(auth()->user()); }
    public static function canDelete($record): bool { return $record->canBeDeletedBy(auth()->user()); }

    public static function getPages(): array
    {
        return [
            'index' => ListNotaPenggantianBiayas::route('/'),
            'create' => CreateNotaPenggantianBiaya::route('/create'),
            'edit' => EditNotaPenggantianBiaya::route('/{record}/edit'),
        ];
    }
}
