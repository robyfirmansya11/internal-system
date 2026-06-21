<?php

namespace App\Filament\Resources\Companies;

use App\Filament\Resources\Companies\Pages\CreateCompany;
use App\Filament\Resources\Companies\Pages\EditCompany;
use App\Filament\Resources\Companies\Pages\ListCompanies;
use App\Filament\Resources\Companies\Schemas\CompanyForm;
use App\Filament\Resources\Companies\Tables\CompaniesTable;
use App\Models\Company;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;
use App\Enums\Role;
use Illuminate\Database\Eloquent\Model;


class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static bool $shouldRegisterNavigation = true;
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationLabel = 'Companies';
    protected static string|UnitEnum|null $navigationGroup = 'Master Data';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice;

    protected static ?string $recordTitleAttribute = 'nama';

    public static function form(Schema $schema): Schema
    {
        return CompanyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CompaniesTable::configure($table);
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

    if (! $user) {
        return false;
    }

    return in_array($user->level, [
        Role::Admin,
        Role::Superadmin,
    ], true);
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
            'index' => ListCompanies::route('/'),
            'create' => CreateCompany::route('/create'),
            'edit' => EditCompany::route('/{record}/edit'),
        ];
    }


}
