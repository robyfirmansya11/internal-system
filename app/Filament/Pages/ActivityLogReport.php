<?php

namespace App\Filament\Pages;

use App\Enums\Role;
use App\Models\ActivityLog;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\Action;

class ActivityLogReport extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected string $view = 'filament.pages.activity-log-report';
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;
    protected static string|\UnitEnum|null $navigationGroup = 'Report';
    protected static ?string $navigationLabel = 'Activity Logs';
    protected static ?string $title = 'Activity Logs';
    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool { return in_array(auth()->user()?->level, [Role::Admin, Role::Superadmin], true); }

    public function table(Table $table): Table
    {
        return $table->query(ActivityLog::query()->with('user')->latest())
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Date & Time')->dateTime('d M Y, H:i')->sortable(),
                Tables\Columns\TextColumn::make('user.name')->label('Performed By')->placeholder('System')->searchable(),
                Tables\Columns\TextColumn::make('subject_type')->label('Document')->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '-')->badge(),
                Tables\Columns\TextColumn::make('subject_id')->label('Record ID')->placeholder('-'),
                Tables\Columns\TextColumn::make('event')->badge(),
                Tables\Columns\TextColumn::make('ip_address')->label('IP Address')->placeholder('-'),
                Tables\Columns\TextColumn::make('changes')->label('Changes')->formatStateUsing(fn ($state): string => $state ? 'View details' : '-')->action(Action::make('viewChanges')->modalHeading('Changed Values')->modalContent(fn (ActivityLog $record) => view('filament.pages.partials.activity-log-changes', ['changes' => $record->changes]))),
            ]);
    }
}
