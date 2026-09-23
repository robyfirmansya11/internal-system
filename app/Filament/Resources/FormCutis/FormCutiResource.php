<?php

namespace App\Filament\Resources\FormCutis;

use App\Enums\Role;
use App\Filament\Resources\FormCutis\Pages\CreateFormCuti;
use App\Filament\Resources\FormCutis\Pages\EditFormCuti;
use App\Filament\Resources\FormCutis\Pages\ListFormCutis;
use App\Filament\Resources\FormCutis\Schemas\FormCutiForm;
use App\Models\FormCuti;
use App\Models\User;
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

class FormCutiResource extends Resource
{
    protected static ?string $model = FormCuti::class;

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Leave Requests';

    protected static ?string $modelLabel = 'Leave Request';

    protected static ?string $pluralModelLabel = 'Leave Requests';

    protected static string|UnitEnum|null $navigationGroup = 'HRIS';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    public static function form(Schema $schema): Schema
    {
        return FormCutiForm::configure($schema);
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

                Tables\Columns\TextColumn::make('jenis_cuti')
                    ->label('Leave Type')
                    ->badge()
                    ->color('gray'),

                // Tables\Columns\IconColumn::make('lampiran')
                //     ->label('Attachment')
                //     ->boolean()
                //     ->trueIcon('heroicon-o-paper-clip')
                //     ->falseIcon('heroicon-o-minus')
                //     ->trueColor('info')
                //     ->falseColor('gray')
                //     ->getStateUsing(fn ($record) => filled($record->lampiran)),

                Tables\Columns\TextColumn::make('tanggal_mulai')
                    ->label('Start Date')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('tanggal_selesai')
                    ->label('End Date')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('jumlah_hari')
                    ->label('Days')
                    ->suffix(' days'),

                // Status dari trait
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
                        $record->isWaitingFinanceManager() => 'Pending Finance Manager Approval',
                        $record->isWaitingAdmin() => 'Pending HR Approval',
                        default => 'Draft',
                    })
                    ->badge()
                    ->color(fn ($state, $record) => match (true) {
                        $record->isApproved() => 'success',
                        $record->isRejected() => 'danger',
                        $record->isWaitingAtasan() => 'warning',
                        $record->isWaitingFinanceManager() => 'primary',
                        $record->isWaitingAdmin() => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('expired_at')
                    ->label('Expired')
                    ->date('d M Y')
                    ->color(fn ($record) => $record->expired_at?->isPast() ? 'danger' : 'success'
                    )
                    ->toggleable(isToggledHiddenByDefault: true),

            ])

            ->actionsColumnLabel('Action')
            ->actions([

                // EDIT
                Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil')
                    ->color('warning')
                    ->url(fn ($record) => static::getUrl('edit', ['record' => $record]))
                    ->visible(fn ($record) => $record->canBeEditedBy(auth()->user())),

                // APPROVE ATASAN
                Action::make('approve_atasan')
                    ->label('Approve')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Leave Request')
                    ->modalDescription('Are you sure you would like to do this?')
                    ->visible(fn ($record) => auth()->user()->isSuperuser()
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

                        $record->refresh();

                        if ($record->isWaitingAdmin()) {
                            $hrds = User::where('level', Role::Admin)
                                ->where('jabatan', 'HRD')
                                ->get();

                            foreach ($hrds as $hrd) {
                                Notification::make()
                                    ->title('Leave Request Pending HR Approval')
                                    ->body("Leave request from {$record->user->name} is awaiting HR approval.")
                                    ->sendToDatabase($hrd);
                            }
                        }

                        Notification::make()
                            ->title('Leave Request Approved')
                            ->body('Your leave request has been approved by your manager.')
                            ->success()
                            ->sendToDatabase($record->user);

                        Notification::make()
                            ->title('Successfully Approved')
                            ->success()
                            ->send();
                    }),

                // APPROVE FINANCE MANAGER
                Action::make('approve_finance_manager')
                    ->label('Approve (Finance Manager)')
                    ->color('primary')
                    ->icon('heroicon-o-banknotes')
                    ->requiresConfirmation()
                    ->modalHeading('Approve — Finance Manager')
                    ->visible(fn ($record) => $record->isWaitingFinanceManager()
                        && $record->isFinanceManagerApprover(auth()->user())
                    )
                    ->action(function ($record) {
                        $result = $record->approveByFinanceManager(auth()->user());

                        if (! $result) {
                            Notification::make()
                                ->title('Approval Failed')
                                ->danger()
                                ->send();

                            return;
                        }

                        $hrds = User::where('level', Role::Admin)
                            ->where('jabatan', FormCuti::LEVEL2_JABATAN)
                            ->get();

                        foreach ($hrds as $hrd) {
                            Notification::make()
                                ->title('Leave Request Pending HR Approval')
                                ->body("Leave request from {$record->user->name} is awaiting HR approval.")
                                ->sendToDatabase($hrd);
                        }

                        Notification::make()
                            ->title('Leave Request Approved by Finance Manager')
                            ->success()
                            ->sendToDatabase($record->user);

                        Notification::make()
                            ->title('Successfully Approved')
                            ->success()
                            ->send();
                    }),

                // APPROVE HRD
                Action::make('approve_hrd')
                    ->label('Approve (HRD)')
                    ->color('success')
                    ->icon('heroicon-o-shield-check')
                    ->requiresConfirmation()
                    ->modalHeading('Approve — HRD')
                    ->visible(fn ($record) => auth()->user()->isAdmin()
                        && auth()->user()->jabatan === FormCuti::LEVEL2_JABATAN
                        && $record->isWaitingAdmin()
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
                            ->title('Leave Request Approved!')
                            ->body('Leave Request has been approved by HRD.')
                            ->success()
                            ->sendToDatabase($record->user);

                        Notification::make()
                            ->title('Successfully Approved')
                            ->success()
                            ->send();
                    }),

                // VIEW ATTACHMENT
                Action::make('view_attachment')
                    ->label('View Attachment')
                    ->icon('heroicon-o-paper-clip')
                    ->color('info')
                    ->url(fn ($record) => asset('storage/'.$record->lampiran))
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => filled($record->lampiran)),

                // REJECT
                Action::make('reject')
                    ->label('Reject')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
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
                                ->title('Reject Failed')
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Leave Request Rejected')
                            ->body("Reason: {$data['rejected_note']}")
                            ->danger()
                            ->sendToDatabase($record->user);

                        Notification::make()
                            ->title('Successfully Rejected')
                            ->success()
                            ->send();
                    }),

                // CANCEL
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
                            ->body('Your submission has been successfully cancelled.')
                            ->success()
                            ->send();
                    }),

                // PRINT PDF
                Action::make('print')
                    ->label('Print PDF')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn ($record) => route('cuti.print', $record->id))
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
        $user = auth()->user();

        return parent::getEloquentQuery()
            ->with(['user', 'department', 'manager', 'financeManager', 'hrd', 'rejector'])
            ->when(
                $user->isUser(),
                fn ($q) => $q->where('user_id', $user->id)
            )
            ->when(
                $user->isSuperuser(),
                // Manager juga harus dapat melihat pengajuan cutinya sendiri.
                fn ($q) => $q->where(function ($query) use ($user) {
                    $query->where('user_id', $user->id)
                        ->orWhereHas('user.profile', function ($profileQuery) use ($user) {
                            $profileQuery->where('atasan_id', $user->id);
                        });
                })
            );
        // Admin & Superadmin lihat semua
    }

    /*
    |--------------------------------------------------------------------------
    | PERMISSIONS
    |--------------------------------------------------------------------------
    */

    public static function canCreate(): bool
    {
        // Semua karyawan, termasuk Manager dan Finance Manager, boleh mengajukan.
        return auth()->check();
    }

    public static function canViewAny(): bool
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

    /*
    |--------------------------------------------------------------------------
    | PAGES
    |--------------------------------------------------------------------------
    */

    public static function getPages(): array
    {
        return [
            'index' => ListFormCutis::route('/'),
            'create' => CreateFormCuti::route('/create'),
            'edit' => EditFormCuti::route('/{record}/edit'),
        ];
    }
}
