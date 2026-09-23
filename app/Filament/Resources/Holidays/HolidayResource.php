<?php

namespace App\Filament\Resources\Holidays;

use App\Enums\Role;
use App\Filament\Resources\Holidays\Pages\CreateHoliday;
use App\Filament\Resources\Holidays\Pages\EditHoliday;
use App\Filament\Resources\Holidays\Pages\ListHolidays;
use App\Models\Holiday;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;

class HolidayResource extends Resource
{
    protected static ?string $model = Holiday::class;
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;
    protected static string|\UnitEnum|null $navigationGroup = 'HRIS';
    protected static ?string $navigationLabel = 'Holidays';
    protected static ?string $modelLabel = 'Holiday';
    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool { return in_array(auth()->user()?->level, [Role::Admin, Role::Superadmin], true); }
    public static function canCreate(): bool { return static::canViewAny(); }
    public static function canEdit($record): bool { return static::canViewAny(); }
    public static function canDelete($record): bool { return static::canViewAny(); }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\DatePicker::make('date')->label('Holiday Date')->required()->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('name')->label('Holiday Name')->required()->maxLength(255),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('date')->columns([
            Tables\Columns\TextColumn::make('date')->label('Date')->date('d M Y')->sortable(),
            Tables\Columns\TextColumn::make('name')->label('Holiday Name')->searchable(),
        ])->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array { return ['index' => ListHolidays::route('/'), 'create' => CreateHoliday::route('/create'), 'edit' => EditHoliday::route('/{record}/edit')]; }
}
