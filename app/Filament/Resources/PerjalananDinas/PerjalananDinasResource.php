<?php

namespace App\Filament\Resources\PerjalananDinas;

use App\Enums\Role;
use App\Filament\Resources\PerjalananDinas\Pages\CreatePerjalananDinas;
use App\Filament\Resources\PerjalananDinas\Pages\EditPerjalananDinas;
use App\Filament\Resources\PerjalananDinas\Pages\ListPerjalananDinas;
use App\Filament\Resources\PerjalananDinas\Schemas\PerjalananDinasForm;
use App\Filament\Resources\PerjalananDinas\Tables\PerjalananDinasTable;
use App\Models\PerjalananDinas;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class PerjalananDinasResource extends Resource
{
    protected static ?string $model = PerjalananDinas::class;

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Travelling Reimbursements';

    protected static ?string $modelLabel = 'Travelling Reimbursement';

    protected static ?string $pluralModelLabel = 'Travelling Reimbursements';

    protected static string|UnitEnum|null $navigationGroup = 'HRIS';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static ?string $recordTitleAttribute = 'keterangan';

    public static function form(Schema $schema): Schema
    {
        return PerjalananDinasForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PerjalananDinasTable::configure($table);
    }

    /**
     * Query utama dengan eager loading dan filter berdasarkan role.
     *
     * - User      → hanya miliknya sendiri
     * - Superuser → hanya bawahan langsung (via atasan_id)
     * - Admin & Superadmin → semua data
     */
    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        $query = parent::getEloquentQuery()
            ->with(['user', 'department', 'company']);

        // User biasa — hanya miliknya
        if ($user->isUser()) {
            return $query->where('user_id', $user->id);
        }

        // Superuser yang JUGA Finance Manager (Summer)
        // Bisa lihat bawahan langsung DAN semua yang menunggu approval FM
        if ($user->isSuperuser() && $user->jabatan === PerjalananDinas::LEVEL2_JABATAN) {
            return $query->where(function ($q) use ($user) {
                $q
                    // Milik sendiri
                    ->where('user_id', $user->id)
                    // Bawahan langsung
                    ->orWhereHas('user.profile', function ($q2) use ($user) {
                        $q2->where('atasan_id', $user->id);
                    })
                    // Semua yang menunggu approval FM (level 2)
                    ->orWhere(function ($q3) {
                        $q3->where('approval_level', 2)
                            ->where('status', 'Pending Approval');
                    });
            });
        }

        // Superuser biasa — hanya bawahan langsung
        if ($user->isSuperuser()) {
            return $query->whereHas('user.profile', function ($q) use ($user) {
                $q->where('atasan_id', $user->id);
            });
        }

        // Admin (HRD, Finance Manager) — lihat semua atau filter by jabatan
        // Admin & Superadmin lihat semua
        return $query;
    }

    /**
     * Untuk soft delete — pastikan record yang sudah dihapus
     * tetap bisa diakses via route binding.
     */
    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
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

    /** Superuser tidak bisa buat pengajuan untuk dirinya sendiri */
    public static function canCreate(): bool
    {
        return ! auth()->user()->isSuperuser();
    }

    /** Hanya bisa edit kalau masih Submitted dan milik sendiri */
    public static function canEdit($record): bool
    {
        return $record->canBeEditedBy(auth()->user());
    }

    /** Hanya bisa delete kalau belum Approved dan milik sendiri */
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
            'index' => ListPerjalananDinas::route('/'),
            'create' => CreatePerjalananDinas::route('/create'),
            'edit' => EditPerjalananDinas::route('/{record}/edit'),
        ];
    }
}
