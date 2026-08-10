<?php

namespace App\Filament\Resources\SuratPerintahBayars;

use App\Enums\Role;
use App\Filament\Resources\SuratPerintahBayars\Pages\CreateSuratPerintahBayar;
use App\Filament\Resources\SuratPerintahBayars\Pages\EditSuratPerintahBayar;
use App\Filament\Resources\SuratPerintahBayars\Pages\ListSuratPerintahBayars;
use App\Filament\Resources\SuratPerintahBayars\Schemas\SuratPerintahBayarForm;
use App\Filament\Resources\SuratPerintahBayars\Tables\SuratPerintahBayarsTable;
use App\Models\SuratPerintahBayar;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class SuratPerintahBayarResource extends Resource
{
    protected static ?string $model = SuratPerintahBayar::class;

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Payment Application Letter';

    protected static ?string $modelLabel = 'Payment Application Letter';

    protected static ?string $pluralModelLabel = 'Payment Application Letters';

    protected static string|UnitEnum|null $navigationGroup = 'Finance, Accounting & Tax';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::CreditCard;

    protected static ?string $recordTitleAttribute = 'no_invoice';

    public static function form(Schema $schema): Schema
    {
        return SuratPerintahBayarForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SuratPerintahBayarsTable::configure($table);
    }

    /**
     * Query utama dengan eager loading dan filter berdasarkan role.
     *
     * - User      → hanya miliknya sendiri
     * - Superuser → hanya bawahan langsung (via atasan_id)
     *   Kalau juga Finance Manager → tambah lihat level 2
     * - Admin & Superadmin → semua data
     */
    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        $query = parent::getEloquentQuery()
            ->with(['user', 'department', 'company']);

        // Cek apakah department user adalah FAT
        $userDepartment = $user->departments->first()?->nama_department;

        $fatNames = [
            'FAT',
            'Finance, Accounting dan Tax',
            'Finance, Accounting & Tax',
            'Finance Accounting Tax',
            'FAT Department',
        ];

        $userIsFat = in_array($userDepartment, $fatNames, true);

        // Department FAT, Superuser, Admin, Superadmin → lihat SEMUA data
        // (semua status: Submitted, Pending, Approved, Paid, Rejected, Cancelled)
        if ($userIsFat || $user->isSuperuser() || $user->isAdmin() || $user->isSuperadmin()) {
            return $query;
        }

        // User biasa (bukan FAT) — hanya lihat miliknya sendiri
        return $query->where('user_id', $user->id);
    }
    /*
    |--------------------------------------------------------------------------
    | PERMISSIONS
    |--------------------------------------------------------------------------
    */

    /** Semua role bisa akses halaman ini */
    public static function canViewAny(): bool
    {
        return true;
    }

    /** Superuser tidak bisa buat SPB untuk dirinya sendiri */
    public static function canCreate(): bool
    {
        return ! auth()->user()->isSuperuser();
    }

    /** Hanya bisa edit kalau masih Submitted & milik sendiri */
    public static function canEdit($record): bool
    {
        return $record->canBeEditedBy(auth()->user());
    }

    /** Hanya bisa delete kalau belum Approved & milik sendiri */
    public static function canDelete($record): bool
    {
        return $record->canBeDeletedBy(auth()->user());
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSuratPerintahBayars::route('/'),
            'create' => CreateSuratPerintahBayar::route('/create'),
            'edit' => EditSuratPerintahBayar::route('/{record}/edit'),
        ];
    }
}
