<?php

namespace App\Filament\Resources\PermohonanStempels;

use App\Filament\Resources\PermohonanStempels\Pages\CreatePermohonanStempel;
use App\Filament\Resources\PermohonanStempels\Pages\EditPermohonanStempel;
use App\Filament\Resources\PermohonanStempels\Pages\ListPermohonanStempels;
use App\Filament\Resources\PermohonanStempels\Schemas\PermohonanStempelForm;
use App\Models\PermohonanStempel;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

use function asset;
use function auth;
use function filled;
use function route;

class PermohonanStempelResource extends Resource
{
    protected static ?string $model = PermohonanStempel::class;

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Stamp Application Letter';

    protected static ?string $modelLabel = 'Stamp Application Letter';

    protected static ?string $pluralModelLabel = 'Stamp Application Letters';

    protected static string|UnitEnum|null $navigationGroup = 'Legal & Litigation';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $recordTitleAttribute = 'nomor_surat';

    public static function form(Schema $schema): Schema
    {
        return PermohonanStempelForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([

                Tables\Columns\TextColumn::make('index')
                    ->label('No.')
                    ->rowIndex(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable(),

                // Tables\Columns\TextColumn::make('department.nama_department')
                //     ->label('Department')
                //     ->badge()
                //     ->color('info'),

                Tables\Columns\TextColumn::make('company.kode')
                    ->label('Company')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('nomor_surat')
                    ->label('Letter Number')
                    ->searchable(),

                Tables\Columns\TextColumn::make('tujuan')
                    ->label('Purpose')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->tujuan),

                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),

                /**
                 * Status dari trait — kapital.
                 */
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'Submitted' => 'gray',
                        'Pending Approval' => 'warning',
                        'Approved' => 'success',
                        'Rejected' => 'danger',
                        'Cancelled' => 'gray',  // ← tambah di semua resource
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('approval_level')
                    ->label('Stage')
                    ->formatStateUsing(fn ($state, $record) => match (true) {
                        $record->isApproved() => 'Approved',
                        $record->isRejected() => 'Rejected',
                        $record->isWaitingAtasan() => 'Pending Manager Approval',
                        $record->isWaitingAdmin() => 'Pending Finance Approval',
                        default => 'Draft',
                    })
                    ->badge()
                    ->color(fn ($state, $record) => match (true) {
                        $record->isApproved() => 'success',
                        $record->isRejected() => 'danger',
                        $record->isWaitingAtasan() => 'warning',
                        $record->isWaitingAdmin() => 'info',
                        default => 'gray',
                    }),

            ])

            ->actionsColumnLabel('Action')
            ->actions([

                /**
                 * EDIT — hanya kalau masih Submitted & milik sendiri.
                 */
                Action::make('edit')
                    ->icon('heroicon-o-pencil')
                    ->color('warning')
                    ->url(fn ($record) => static::getUrl('edit', ['record' => $record]))
                    ->visible(fn ($record) => $record->canBeEditedBy(auth()->user())),

                /**
                 * APPROVE — hanya atasan langsung dengan hak approval.
                 * Permohonan Stempel hanya 1 level approval.
                 */
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Stamp Application Letter')
                    ->modalDescription('Are you sure you want to approve this stamp application request?')
                    ->visible(fn ($record) => auth()->user()->canApprove()
                        && $record->isWaitingAtasan()
                        && $record->isValidAtasan(auth()->user())
                    )
                    ->action(function ($record) {
                        $result = $record->approveByAtasan(auth()->user());

                        if (! $result) {
                            Notification::make()
                                ->title('Approval Failed')
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Stamp Application Approved')
                            ->success()
                            ->sendToDatabase($record->user);

                        Notification::make()
                            ->title('Approval Successful')
                            ->success()
                            ->send();
                    }),
                /**
                 * APPROVE — Finance Manager, khusus untuk pengajuan dari Manager
                 * yang di-skip langsung ke level 2 (lihat CreatePermohonanStempel.php).
                 */
                Action::make('approve_finance')
                    ->label('Approve (Finance)')
                    ->icon('heroicon-o-shield-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve — Finance Manager')
                    ->visible(fn ($record) => auth()->user()->jabatan === PermohonanStempel::LEVEL2_JABATAN
                        && $record->isWaitingAdmin()
                        && $record->user_id !== auth()->id()
                    )
                    ->action(function ($record) {
                        $result = $record->approveByAdmin(auth()->user());

                        if (! $result) {
                            Notification::make()
                                ->title('Approval Failed')
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Stamp Application Approved')
                            ->success()
                            ->sendToDatabase($record->user);

                        Notification::make()
                            ->title('Approval Successful')
                            ->success()
                            ->send();
                    }),

                /**
                 * REJECT — hanya atasan langsung yang bisa reject.
                 */
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('rejected_note')
                            ->label('Reason for Rejection')
                            ->required()
                            ->rows(3),
                    ])
                    ->visible(fn ($record) => $record->canBeApprovedBy(auth()->user())
                    )
                    ->action(function ($record, array $data) {
                        $result = $record->reject(auth()->user(), $data['rejected_note']);

                        if (! $result) {
                            Notification::make()
                                ->title('Rejection Failed')
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Stamp Application Rejected')
                            ->body("Reason: {$data['rejected_note']}")
                            ->danger()
                            ->sendToDatabase($record->user);

                        Notification::make()
                            ->title('Rejection Successful')
                            ->success()
                            ->send();
                    }),

                /**
                 * CANCEL — hanya pemilik sendiri yang bisa cancel.
                 * Cancelled status tetap disimpan untuk audit.
                 */
                Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Cancel Submission')
                    ->modalDescription('Are you sure you want to cancel this request? The record will be retained with a Cancelled status for audit purposes.')
                    ->modalSubmitActionLabel('Yes, Cancel Request')
                    ->modalCancelActionLabel('No, Keep It')
                    ->visible(fn ($record) => $record->canBeCancelledBy(auth()->user()))
                    ->action(function ($record) {
                        $result = $record->cancel(auth()->user());

                        if (! $result) {
                            Notification::make()
                                ->title('Cancellation Failed')
                                ->body('This request cannot be cancelled. It may have already been approved, rejected, or cancelled.')
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Request Cancelled')
                            ->body('Your stamp application request has been cancelled successfully.')
                            ->success()
                            ->send();
                    }),

                /**
                 * VIEW LAMPIRAN — buka file lampiran di tab baru.
                 * Hanya muncul kalau lampiran ada.
                 */
                Action::make('lampiran')
                    ->label('Attachment')
                    ->icon('heroicon-o-paper-clip')
                    ->color('gray')
                    ->url(fn ($record) => $record->lampiran
                        ? asset('storage/'.$record->lampiran)
                        : null
                    )
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => filled($record->lampiran)),

                /**
                 * PRINT PDF.
                 */
                Action::make('print')
                    ->label('Print PDF')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn ($record) => route('permohonan-stempel.print', $record->id))
                    ->openUrlInNewTab(),

            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | QUERY
    |--------------------------------------------------------------------------
    */

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user', 'department', 'company']);
    }

    /*
    |--------------------------------------------------------------------------
    | PERMISSIONS
    |--------------------------------------------------------------------------
    */

    public static function canViewAny(): bool
    {
        return true;
    }

    public static function canCreate(): bool
    {
        return true;
    }

    public static function canEdit($record): bool
    {
        return $record->canBeEditedBy(auth()->user());
    }

    public static function canDelete($record): bool
    {
        return $record->canBeDeletedBy(auth()->user());
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPermohonanStempels::route('/'),
            'create' => CreatePermohonanStempel::route('/create'),
            'edit' => EditPermohonanStempel::route('/{record}/edit'),
        ];
    }
}
