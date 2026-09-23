<?php

namespace App\Filament\Resources\NotaPenggantianBiayas\Tables;

use App\Models\NotaPenggantianBiaya;
use App\Models\User;
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

class NotaPenggantianBiayasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('index')->label('No.')->rowIndex(),
                TextColumn::make('tanggal')->label('Date')->date('d M Y')->sortable(),
                TextColumn::make('user.name')->label('Employee')->searchable()->sortable(),
                TextColumn::make('company.kode')->label('PT')->badge()->color('gray'),
                /*             TextColumn::make('details')
                    ->label('Description')
                    ->formatStateUsing(fn ($state, $record) => $record->details->pluck('keterangan')->filter()->implode(', '))
                    ->limit(45), */
                TextColumn::make('jumlah_total')->label('Total Amount')->money('IDR')->sortable(),
                TextColumn::make('lampiran')
                    ->label('Attachment')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'View Attachment' : 'No Attachment')
                    ->color(fn ($state) => $state ? 'success' : 'gray')
                    ->url(fn ($record) => $record->lampiran ? asset('storage/'.$record->lampiran) : null)
                    ->openUrlInNewTab(),
                TextColumn::make('status')->label('Status')->badge()->color(fn (string $state) => match ($state) {
                    'Approved' => 'success', 'Rejected' => 'danger', 'Pending Approval' => 'warning',
                    'Cancelled' => 'gray', default => 'gray',
                }),
                TextColumn::make('approval_level')->label('Approval Stage')->badge()
                    ->formatStateUsing(fn ($state, $record) => match (true) {
                        $record->isApproved() => 'Approved',
                        $record->isRejected() => 'Rejected',
                        $record->isWaitingAtasan() => 'Pending Manager Approval',
                        $record->isWaitingAdmin() => 'Pending Finance Approval',
                        default => 'Submitted',
                    })
                    ->color(fn ($state, $record) => match (true) {
                        $record->isApproved() => 'success', $record->isRejected() => 'danger',
                        $record->isPending() => 'warning', default => 'gray',
                    }),
            ])
            ->filters([TrashedFilter::make()])
            ->actionsColumnLabel('Actions')
            ->actions([
                Action::make('edit')->icon('heroicon-o-pencil')->color('warning')
                    ->url(fn ($record) => route('filament.admin.resources.nota-penggantian-biayas.edit', $record))
                    ->visible(fn ($record) => $record->canBeEditedBy(auth()->user())),
                Action::make('approve_manager')->label('Approve')->icon('heroicon-o-check-circle')->color('success')
                    ->requiresConfirmation()->modalHeading('Approve — Manager')
                    ->visible(fn ($record) => auth()->user()->isSuperuser() && $record->isWaitingAtasan() && $record->isValidAtasan(auth()->user()))
                    ->action(function ($record): void {
                        if (! $record->approveByAtasan(auth()->user())) {
                            Notification::make()->title('Approval Failed')->danger()->send();

                            return;
                        }

                        $record->refresh();
                        if ($record->isApproved()) {
                            Notification::make()->title('Expense Reimbursement Note Approved')->success()->sendToDatabase($record->user);
                        } else {
                            User::where('jabatan', NotaPenggantianBiaya::LEVEL2_JABATAN)->whereKeyNot(auth()->id())->get()
                                ->each(fn (User $financeManager) => Notification::make()->title('Expense Reimbursement Note Pending Finance Approval')->sendToDatabase($financeManager));
                            Notification::make()->title('Forwarded to Finance Manager')->success()->sendToDatabase($record->user);
                        }
                        Notification::make()->title('Approval Successful')->success()->send();
                    }),
                Action::make('approve_finance')->label('Approve (Finance)')->icon('heroicon-o-shield-check')->color('success')
                    ->requiresConfirmation()->modalHeading('Approve — Finance Manager')
                    ->visible(fn ($record) => auth()->user()->jabatan === NotaPenggantianBiaya::LEVEL2_JABATAN && $record->isWaitingAdmin() && $record->user_id !== auth()->id())
                    ->action(function ($record): void {
                        if (! $record->approveByAdmin(auth()->user())) {
                            Notification::make()->title('Approval Failed')->danger()->send();

                            return;
                        }
                        Notification::make()->title('Expense Reimbursement Note Approved')->success()->sendToDatabase($record->user);
                        Notification::make()->title('Approval Successful')->success()->send();
                    }),
                Action::make('reject')->label('Reject')->icon('heroicon-o-x-circle')->color('danger')->requiresConfirmation()
                    ->form([Textarea::make('rejected_note')->label('Reason for Rejection')->required()->rows(3)])
                    ->visible(fn ($record) => $record->canBeApprovedBy(auth()->user()))
                    ->action(function ($record, array $data): void {
                        if (! $record->reject(auth()->user(), $data['rejected_note'])) {
                            Notification::make()->title('Rejection Failed')->danger()->send();

                            return;
                        }
                        Notification::make()->title('Expense Reimbursement Note Rejected')->body("Reason: {$data['rejected_note']}")->danger()->sendToDatabase($record->user);
                        Notification::make()->title('Rejection Successful')->success()->send();
                    }),
                Action::make('cancel')->label('Cancel')->icon('heroicon-o-x-mark')->color('danger')->requiresConfirmation()
                    ->modalHeading('Cancel Submission')->modalDescription('Are you sure you want to cancel this request?')
                    ->visible(fn ($record) => $record->canBeCancelledBy(auth()->user()))
                    ->action(function ($record): void {
                        if (! $record->cancel(auth()->user())) {
                            Notification::make()->title('Cancellation Failed')->danger()->send();

                            return;
                        }
                        Notification::make()->title('Request Cancelled')->success()->send();
                    }),
                Action::make('print')->label('Print PDF')->icon('heroicon-o-printer')->color('info')
                    ->url(fn ($record) => route('nota-penggantian-biaya.pdf', $record))->openUrlInNewTab(),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make(), ForceDeleteBulkAction::make(), RestoreBulkAction::make()])]);
    }
}
