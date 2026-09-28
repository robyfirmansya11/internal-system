<?php

namespace App\Filament\Resources\MealClaims\Schemas;

use App\Models\Company;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MealClaimForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->schema([
                Select::make('company_id')
                    ->label('PT / Company')
                    ->options(Company::query()->orderBy('nama')->pluck('nama', 'id'))
                    ->searchable()
                    ->required(),
                DatePicker::make('claim_date')
                    ->label('Submission Date')
                    ->default(today())
                    ->required(),
                Textarea::make('employee_note')
                    ->label('Employee Note')
                    ->placeholder('Optional note for the Claim Admin.')
                    ->columnSpanFull(),
                Repeater::make('items')
                    ->label('Meal Receipts')
                    ->relationship('items')
                    ->defaultItems(1)
                    ->minItems(1)
                    ->maxItems(20)
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn ($state, callable $set, callable $get) => self::calculateTotal($get, $set))
                    ->schema([
                        FileUpload::make('receipt_path')
                            ->label('Receipt Photo')
                            ->disk('private')
                            ->directory('meal-claims/receipts')
                            ->visibility('private')
                            ->image()
                            ->imageEditor()
                            ->acceptedFileTypes(['image/jpeg', 'image/png'])
                            ->maxSize(5120)
                            ->required(),
                        DatePicker::make('meal_date')
                            ->label('Meal Date')
                            ->default(today())
                            ->required(),
                        Select::make('meal_type')
                            ->label('Meal Type')
                            ->options(['Breakfast' => 'Breakfast', 'Lunch' => 'Lunch', 'Dinner' => 'Dinner', 'Meal' => 'Other Meal'])
                            ->default('Meal')
                            ->required(),
                        TextInput::make('merchant')
                            ->label('Restaurant / Merchant')
                            ->maxLength(255),
                        TextInput::make('amount')
                            ->label('Claim Amount')
                            ->numeric()
                            ->prefix('Rp')
                            ->minValue(0)
                            ->default(0)
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set, callable $get) => self::calculateTotal($get, $set)),
                        Textarea::make('note')
                            ->label('Receipt Note')
                            ->placeholder('Optional note, for example attendees or business purpose.')
                            ->columnSpanFull(),
                    ])
                    ->columns(3)
                    ->createItemButtonLabel('Add Receipt')
                    ->columnSpanFull(),
                TextInput::make('total_amount')
                    ->label('Total Claim Amount')
                    ->prefix('Rp')
                    ->readOnly()
                    ->required(),
            ]);
    }

    private static function calculateTotal(callable $get, callable $set): void
    {
        $items = $get('../../items') ?? [];
        $total = collect($items)->sum(fn (array $item) => (float) ($item['amount'] ?? 0));

        $set('../../total_amount', round($total, 2));
    }
}
