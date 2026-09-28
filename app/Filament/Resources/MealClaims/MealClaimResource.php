<?php

namespace App\Filament\Resources\MealClaims;

use App\Filament\Resources\MealClaims\Pages\CreateMealClaim;
use App\Filament\Resources\MealClaims\Pages\EditMealClaim;
use App\Filament\Resources\MealClaims\Pages\ListMealClaims;
use App\Filament\Resources\MealClaims\Schemas\MealClaimForm;
use App\Filament\Resources\MealClaims\Tables\MealClaimsTable;
use App\Models\MealClaim;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class MealClaimResource extends Resource
{
    protected static ?string $model = MealClaim::class;
    protected static ?int $navigationSort = 5;
    protected static ?string $navigationLabel = 'Meal Claims';
    protected static ?string $modelLabel = 'Meal Claim';
    protected static ?string $pluralModelLabel = 'Meal Claims';
    protected static string|UnitEnum|null $navigationGroup = 'Finance, Accounting & Tax';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::ReceiptPercent;

    public static function form(Schema $schema): Schema { return MealClaimForm::configure($schema); }
    public static function table(Table $table): Table { return MealClaimsTable::configure($table); }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $query = parent::getEloquentQuery()->with(['user', 'company', 'department', 'items']);

        if ($user->isAdmin() || $user->isSuperadmin() || $user->isFinanceManager() || $user->isFinanceAccountingTaxMember()) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($user): void {
            $query->where('user_id', $user->id);

            if ($user->isSuperuser()) {
                $query->orWhereHas('user.profile', fn (Builder $subordinates) => $subordinates->where('atasan_id', $user->id));
            }
        });
    }

    public static function canViewAny(): bool { return true; }
    public static function canCreate(): bool { return true; }
    public static function canEdit($record): bool { return $record->canBeEditedBy(auth()->user()); }
    public static function canDelete($record): bool { return $record->canBeDeletedBy(auth()->user()); }

    public static function getPages(): array
    {
        return [
            'index' => ListMealClaims::route('/'),
            'create' => CreateMealClaim::route('/create'),
            'edit' => EditMealClaim::route('/{record}/edit'),
        ];
    }
}
