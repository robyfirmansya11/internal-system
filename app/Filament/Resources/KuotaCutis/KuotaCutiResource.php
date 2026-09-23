<?php

namespace App\Filament\Resources\KuotaCutis;

use App\Filament\Resources\KuotaCutis\Pages\CreateKuotaCuti;
use App\Filament\Resources\KuotaCutis\Pages\EditKuotaCuti;
use App\Filament\Resources\KuotaCutis\Pages\ListKuotaCutis;
use App\Filament\Resources\KuotaCutis\Schemas\KuotaCutiForm;
use App\Filament\Resources\KuotaCutis\Tables\KuotaCutisTable;
use App\Models\KuotaCuti;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use App\Enums\Role;
use Illuminate\Database\Eloquent\Model;

use UnitEnum;

class KuotaCutiResource extends Resource
{
    protected static ?string $model = KuotaCuti::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $recordTitleAttribute = 'tahun';

    protected static string|UnitEnum|null $navigationGroup = 'HRIS';

    protected static ?string $navigationLabel = 'Leave Quotas';

    protected static ?string $modelLabel = 'Leave Quota';

    protected static ?string $pluralModelLabel = 'Leave Quotas';



    /*
    |--------------------------------------------------------------------------
    | FORM & TABLE
    |--------------------------------------------------------------------------
    */

    public static function form(Schema $schema): Schema
    {
        return KuotaCutiForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KuotaCutisTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
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
            'index' => ListKuotaCutis::route('/'),
            'create' => CreateKuotaCuti::route('/create'),
            'edit' => EditKuotaCuti::route('/{record}/edit'),
        ];
    }
}
