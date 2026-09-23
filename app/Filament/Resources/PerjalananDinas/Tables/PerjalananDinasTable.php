<?php

namespace App\Filament\Resources\PerjalananDinas\Tables;

use App\Models\PerjalananDinas;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class PerjalananDinasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([

                TextColumn::make('index')
                    ->label('No.')
                    ->rowIndex(),

                TextColumn::make('user.name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('keterangan')
                    ->label('Description')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->keterangan),

                TextColumn::make('total')
                    ->label('Total Amount')
                    ->money('IDR'),

                /**
                 * Status badge — menggunakan status dari HasApprovalWorkflow trait.
                 */
                TextColumn::make('status')
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

                /**
                 * Stage approval — menunjukkan posisi dalam alur approval.
                 */
                TextColumn::make('approval_level')
                    ->label('Stage')
                    ->formatStateUsing(fn ($state, $record) => match (true) {
                        $record->isApproved() => 'Approved',
                        $record->isRejected() => 'Rejected',
                        $record->isWaitingAtasan() => 'Pending Manager Approval',

                        $record->isWaitingAdmin() => 'Waiting '.PerjalananDinas::LEVEL2_JABATAN,
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

            ->filters([
                TrashedFilter::make(),
            ])

            ->recordActionsColumnLabel('Actions')
            ->recordActions([

                /**
                 * EDIT — hanya bisa kalau masih Submitted & milik sendiri.
                 */
                Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil')
                    ->color('warning')
                    ->url(fn ($record) => \App\Filament\Resources\PerjalananDinas\PerjalananDinasResource::getUrl('edit', ['record' => $record]))
                    ->visible(fn ($record) => $record->canBeEditedBy(auth()->user())),

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

                /**
                 * APPROVE ATASAN (level 1).
                 * Hanya Superuser yang merupakan atasan langsung karyawan.
                 */
                Action::make('approve_atasan')
                    ->label('Approve')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Business Trip Request')
                    ->modalDescription('Are you sure you want to approve this request??')
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

                        if ($record->isApproved()) {

                            Notification::make()
                                ->title('Travelling Reimbursement Approved!')
                                ->body('Your travelling reimburstment has been approved.')
                                ->success()
                                ->sendToDatabase($record->user);

                        } elseif ($record->isWaitingAdmin()) {

                            $fms = \App\Models\User::query()
                                ->where('jabatan', PerjalananDinas::LEVEL2_JABATAN)
                                ->whereKeyNot(auth()->id()) // jangan kirim ke approver yang baru saja approve
                                ->get();

                            foreach ($fms as $fm) {

                                Notification::make()
                                    ->title('Travel Reimbursement Pending Finance Approval')
                                    ->body("{$record->user->name} 's travel reimbursement request is awaiting Finance Manager approval.")
                                    ->sendToDatabase($fm);
                            }

                            Notification::make()
                                ->title('Manager Approval Completed')
                                ->body('Your reimbursement request has been forwarded to the Finance Manager for final approval..')
                                ->success()
                                ->sendToDatabase($record->user);
                        }

                        Notification::make()
                            ->title('Approval Successful')
                            ->success()
                            ->send();
                    }),

                /**
                 * APPROVE FINANCE MANAGER (level 2).
                 * Jabatan harus 'Finance Manager' — level bisa Superuser atau Admin.
                 */
                Action::make('approve_finance')
                    ->label('Approve (Finance)')
                    ->color('success')
                    ->icon('heroicon-o-shield-check')
                    ->requiresConfirmation()
                    ->modalHeading('Approve — Finance Manager')
                    ->visible(fn ($record) =>
                        // Cek jabatan saja, tidak peduli level
                        auth()->user()->jabatan === PerjalananDinas::LEVEL2_JABATAN
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
                            ->title('Travelling Reimbursement Approved!')
                            ->success()
                            ->sendToDatabase($record->user);

                        Notification::make()
                            ->title('Approval Successful')
                            ->success()
                            ->send();
                    }),

                /**
                 * REJECT — bisa dilakukan oleh siapapun yang punya hak approve
                 * di level saat ini (via canBeApprovedBy dari trait).
                 */
                Action::make('reject')
                    ->label('Reject')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->requiresConfirmation()
                    ->form([
                        Textarea::make('rejected_note')
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
                            ->title('Rejected')
                            ->body("Reason: {$data['rejected_note']}")
                            ->danger()
                            ->sendToDatabase($record->user);

                        Notification::make()
                            ->title('Rejection Successful')
                            ->success()
                            ->send();
                    }),

                /**
                 * EXPORT PDF — buka PDF di tab baru.
                 */
                Action::make('exportPdf')
                    ->label('Print PDF')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn ($record) => route('perjalanan-dinas.pdf', $record->id))
                    ->openUrlInNewTab(),

            ])

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
