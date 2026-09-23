<?php

namespace App\Filament\Pages;

use App\Enums\Role;
use App\Models\ApprovalHistory;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;

class ApprovalAuditTrail extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected string $view = 'filament.pages.approval-audit-trail';
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;
    protected static string|\UnitEnum|null $navigationGroup = 'Report';
    protected static ?string $navigationLabel = 'Approval Audit Trail';
    protected static ?string $title = 'Approval Audit Trail';
    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return in_array(auth()->user()?->level, [Role::Admin, Role::Superadmin], true);
    }

    public function table(Table $table): Table
    {
        return $table->query(ApprovalHistory::query()->with('user')->latest())
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Date & Time')->dateTime('d M Y, H:i')->sortable(),
                Tables\Columns\TextColumn::make('approvable_type')->label('Document')->formatStateUsing(fn (string $state): string => class_basename($state))->badge(),
                Tables\Columns\TextColumn::make('approvable_id')->label('Document ID')->sortable(),
                Tables\Columns\TextColumn::make('user.name')->label('Performed By')->searchable(),
                Tables\Columns\TextColumn::make('action')->badge(),
                Tables\Columns\TextColumn::make('status_before')->label('Before')->placeholder('-'),
                Tables\Columns\TextColumn::make('status_after')->label('After')->placeholder('-'),
                Tables\Columns\TextColumn::make('approval_level')->label('Level')->placeholder('-'),
                Tables\Columns\TextColumn::make('note')->label('Note')->limit(60)->tooltip(fn (ApprovalHistory $record): ?string => $record->note),
            ]);
    }
}
