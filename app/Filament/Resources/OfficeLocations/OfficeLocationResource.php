<?php

namespace App\Filament\Resources\OfficeLocations;

use App\Filament\Resources\OfficeLocations\Pages\CreateOfficeLocation;
use App\Filament\Resources\OfficeLocations\Pages\EditOfficeLocation;
use App\Filament\Resources\OfficeLocations\Pages\ListOfficeLocations;
use App\Models\OfficeLocation;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

class OfficeLocationResource extends Resource
{
    protected static ?string $model = OfficeLocation::class;

    protected static ?string $navigationLabel = 'Office Locations';

    protected static ?string $modelLabel = 'Office Location';

    protected static ?string $pluralModelLabel = 'Office Locations';

    protected static string|UnitEnum|null $navigationGroup = 'HRIS';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([

                TextInput::make('name')
                    ->label('Office Location Name')
                    ->placeholder('Example: Kantor Pusat Jakarta')
                    ->required()
                    ->maxLength(255),

                TextInput::make('latitude')
                    ->label('Latitude')
                    ->numeric()
                    ->required()
                    ->placeholder('-6.200000')
                    ->helperText('Copy from Google Maps — right-click on the location, copy coordinates.'),

                TextInput::make('longitude')
                    ->label('Longitude')
                    ->numeric()
                    ->required()
                    ->placeholder('106.816666'),

                TextInput::make('radius')
                    ->label('Radius (meter)')
                    ->numeric()
                    ->default(100)
                    ->required()
                    ->suffix('meter')
                    ->helperText('Maximum distance employees can be from this point to be able to clock in.'),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->helperText('Only 1 location should be active at a time.'),

            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([

                TextColumn::make('index')
                    ->label('No.')
                    ->rowIndex(),

                TextColumn::make('name')
                    ->label('Office Location')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('latitude')
                    ->label('Latitude')
                    ->copyable(),

                TextColumn::make('longitude')
                    ->label('Longitude')
                    ->copyable(),

                TextColumn::make('radius')
                    ->label('Radius')
                    ->suffix(' m')
                    ->badge()
                    ->color('info'),

                ToggleColumn::make('is_active')
                    ->label('Active'),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y')
                    ->toggleable(isToggledHiddenByDefault: true),

            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | PERMISSIONS — hanya HRD & Superadmin (IT)
    |--------------------------------------------------------------------------
    */

    public static function canViewAny(): bool
    {
        return self::userHasAccess();
    }

    public static function canCreate(): bool
    {
        return self::userHasAccess();
    }

    public static function canEdit($record): bool
    {
        return self::userHasAccess();
    }

    public static function canDelete($record): bool
    {
        return self::userHasAccess();
    }

    /**
     * Cek apakah user adalah Superadmin (IT) atau Admin dengan jabatan HRD.
     */
    /**
     * Cek apakah user adalah Superadmin (IT) atau HRD.
     */
    protected static function userHasAccess(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return $user->isSuperadmin() || $user->isHRD();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOfficeLocations::route('/'),
            'create' => CreateOfficeLocation::route('/create'),
            'edit' => EditOfficeLocation::route('/{record}/edit'),
        ];
    }
}
