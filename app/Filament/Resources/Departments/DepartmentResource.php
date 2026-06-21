<?php

namespace App\Filament\Resources\Departments;

use App\Filament\Resources\Departments\Pages\CreateDepartment;
use App\Filament\Resources\Departments\Pages\EditDepartment;
use App\Filament\Resources\Departments\Pages\ListDepartments;
use App\Filament\Resources\Departments\Schemas\DepartmentForm;
use App\Filament\Resources\Departments\Tables\DepartmentsTable;
use App\Models\Department;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;
use App\Enums\Role;
use Illuminate\Database\Eloquent\Model;


class DepartmentResource extends Resource
{
    protected static ?string $model = Department::class;
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Departments';
    protected static string|UnitEnum|null $navigationGroup = 'Master Data';


    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return DepartmentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DepartmentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }
    protected static function allowedRoles(): array
{
        return [
        Role::Admin->value,
        Role::Superadmin->value,
    ];
}

    /*
    |--------------------------------------------------------------------------
    | ROLE PERMISSION (SAMAKAN DENGAN USER RESOURCE)
    |--------------------------------------------------------------------------
    */

/*
|--------------------------------------------------------------------------
| ROLE PERMISSION (FILAMENT V5 CLEAN VERSION)
|--------------------------------------------------------------------------
*/

public static function canAccess(): bool
{
    $user = filament()->auth()->user();

    return $user?->level?->isAdmin() ?? false;
}

public static function canViewAny(): bool
{
    return static::canAccess();
}

public static function canCreate(): bool
{
    return static::canAccess();
}

public static function canEdit(Model $record): bool
{
    return static::canAccess();
}

public static function canDelete(Model $record): bool
{
    return static::canAccess();
}

    /*
    |--------------------------------------------------------------------------
    | PAGES
    |--------------------------------------------------------------------------
    */

    public static function getPages(): array
    {
        return [
            'index' => ListDepartments::route('/'),
            'create' => CreateDepartment::route('/create'),
            'edit' => EditDepartment::route('/{record}/edit'),
        ];
    }
}
