<?php

namespace App\Filament\Resources\Lemburs;

use App\Filament\Resources\Lemburs\Pages\CreateLembur;
use App\Filament\Resources\Lemburs\Pages\EditLembur;
use App\Filament\Resources\Lemburs\Pages\ListLemburs;
use App\Filament\Resources\Lemburs\Schemas\LemburForm;
use App\Filament\Resources\Lemburs\Tables\LembursTable;
use App\Models\Lembur;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class LemburResource extends Resource
{
    protected static ?string $model = Lembur::class;

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Overtime Requests';

    protected static ?string $modelLabel = 'Overtime Request';

    protected static ?string $pluralModelLabel = 'Overtime Requests';

    protected static string|UnitEnum|null $navigationGroup = 'HRIS';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Clock;

    protected static ?string $recordTitleAttribute = 'tanggal_lembur';

    public static function form(Schema $schema): Schema
    {
        return LemburForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LembursTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        return parent::getEloquentQuery()
            ->with(['user', 'department'])
            ->addSelect([
                'total_month' => Lembur::selectRaw('COALESCE(SUM(jumlah_jam_lembur), 0)')
                    ->from('lemburs as l2')
                    ->whereColumn('l2.user_id', 'lemburs.user_id')
                    ->whereColumn('l2.bulan_lembur', 'lemburs.bulan_lembur')
                    ->whereNull('l2.deleted_at'),
            ])
            ->when(
                $user->isUser(),
                fn ($q) => $q->where('user_id', $user->id)
            )
            ->when(
                $user->isSuperuser(),
                fn ($q) => $q->whereHas('user.profile', function ($q) use ($user) {
                    $q->where('atasan_id', $user->id);
                })
            );
    }

    public static function canCreate(): bool
    {
        return ! auth()->user()->isSuperuser();
    }

    public static function canEdit($record): bool
    {
        return $record->canBeEditedBy(auth()->user());
    }

    public static function canViewAny(): bool
    {
        return true;
    }

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
            'index' => ListLemburs::route('/'),
            'create' => CreateLembur::route('/create'),
            'edit' => EditLembur::route('/{record}/edit'),
        ];
    }
}
