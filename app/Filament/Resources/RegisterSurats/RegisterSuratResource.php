<?php

namespace App\Filament\Resources\RegisterSurats;

use App\Filament\Resources\RegisterSurats\Pages\CreateRegisterSurat;
use App\Filament\Resources\RegisterSurats\Pages\EditRegisterSurat;
use App\Filament\Resources\RegisterSurats\Pages\ListRegisterSurats;
use App\Filament\Resources\RegisterSurats\Schemas\RegisterSuratForm;
use App\Filament\Resources\RegisterSurats\Tables\RegisterSuratsTable;
use App\Models\RegisterSurat;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class RegisterSuratResource extends Resource
{
    /**
     * Model yang digunakan oleh resource.
     */
    protected static ?string $model = RegisterSurat::class;

    /**
     * Konfigurasi menu navigasi.
     */
    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Register Letters';

    protected static string|UnitEnum|null $navigationGroup = 'Legal & Litigation';

    protected static ?string $modelLabel = 'Register Letter';

    protected static string|BackedEnum|null $navigationIcon =
        Heroicon::OutlinedBuildingLibrary;

    protected static ?string $recordTitleAttribute = 'no_surat';

    /**
     * Form schema.
     */
    public static function form(Schema $schema): Schema
    {
        return RegisterSuratForm::configure($schema);
    }

    /**
     * Table schema.
     */
    public static function table(Table $table): Table
    {
        return RegisterSuratsTable::configure($table);
    }

    /**
     * Relation manager.
     */
    public static function getRelations(): array
    {
        return [];
    }

    /**
     * Register letter hanya dapat dihapus oleh user yang membuatnya.
     */
    public static function canDelete(Model $record): bool
    {
        return $record->user_id === auth()->id();
    }

    public static function canForceDelete(Model $record): bool
    {
        return static::canDelete($record);
    }

    /**
     * Resource pages.
     */
    public static function getPages(): array
    {
        return [
            'index' => ListRegisterSurats::route('/'),
            'create' => CreateRegisterSurat::route('/create'),
            'edit' => EditRegisterSurat::route('/{record}/edit'),
        ];
    }

    /**
     * Tetap bisa membuka record yang sudah soft delete.
     */
    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    /**
     * Semua user dapat melihat seluruh register surat.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }
}
