<?php

namespace App\Filament\Resources\Lemburs\Tables;

use App\Enums\Role;
use App\Models\Lembur;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LembursTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('tanggal_lembur', 'desc')
            ->checkIfRecordIsSelectableUsing(
                fn (Lembur $record): bool => ! $record->isApproved()
                    && ! $record->isRejected()
                    && ! $record->isCancelled()
            )
            ->checkIfRecordIsSelectableUsing(
                fn (Lembur $record): bool => auth()->user()
                    && $record->canBeApprovedBy(auth()->user())
            )
            ->columns([

                TextColumn::make('index')
                    ->rowIndex()
                    ->label('No'),

                TextColumn::make('user.name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('tanggal_lembur')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('mulai_lembur')
                    ->label('Start')
                    ->time('H:i')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('selesai_lembur')
                    ->label('End')
                    ->time('H:i')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('jumlah_jam_lembur')
                    ->label('Hours')
                    ->numeric(2)
                    ->suffix(' hours'),

                TextColumn::make('total_month')
                    ->label('Total Monthly Hours')
                    ->numeric(2)
                    ->suffix(' hours')
                    ->color('warning'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'Pending Approval' => 'warning',
                        'Approved' => 'success',
                        'Rejected' => 'danger',
                        'Cancelled' => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('approval_level')
                    ->label('Approval Stage')
                    ->formatStateUsing(fn ($state, $record) => match (true) {
                        $record->isCancelled() => 'Cancelled',
                        $record->isApproved() => 'Approved',
                        $record->isRejected() => 'Rejected',
                        $record->isWaitingAtasan() => 'Pending Manager Review',
                        $record->isWaitingAdmin() => 'Pending HR Approval',
                        $record->isSubmitted() => 'Submitted',
                        default => 'Submitted',
                    })
                    ->badge()
                    ->color(fn ($state, $record) => match (true) {
                        $record->isCancelled() => 'gray',
                        $record->isApproved() => 'success',
                        $record->isRejected() => 'danger',
                        $record->isWaitingAtasan() => 'warning',
                        $record->isWaitingAdmin() => 'info',
                        $record->isSubmitted() => 'gray',
                        default => 'gray',
                    }),

            ])

            ->actionsColumnLabel('Actions')

            ->actions([

                Action::make('detail')
                    ->label('Details')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading('Overtime Details')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->form([

                        Forms\Components\TextInput::make('employee')
                            ->label('Employee')
                            ->default(fn ($record) => $record->user->name)
                            ->disabled(),

                        Forms\Components\TextInput::make('department')
                            ->label('Department')
                            ->default(fn ($record) => $record->department->nama_department)
                            ->disabled(),

                        Forms\Components\DatePicker::make('tanggal')
                            ->label('Date')
                            ->default(fn ($record) => $record->tanggal_lembur)
                            ->disabled(),

                        Forms\Components\TimePicker::make('start')
                            ->label('Start Time')
                            ->default(fn ($record) => $record->mulai_lembur)
                            ->disabled(),

                        Forms\Components\TimePicker::make('finish')
                            ->label('End Time')
                            ->default(fn ($record) => $record->selesai_lembur)
                            ->disabled(),

                        Forms\Components\TextInput::make('hours')
                            ->label('Total Hours')
                            ->default(fn ($record) => $record->jumlah_jam_lembur.' hours')
                            ->disabled(),

                        Forms\Components\Textarea::make('uraian_pekerjaan')
                            ->label('Work Description')
                            ->default(fn ($record) => $record->uraian_pekerjaan)
                            ->rows(4)
                            ->disabled(),

                        Forms\Components\Textarea::make('reject')
                            ->label('Rejection Reason')
                            ->default(fn ($record) => $record->rejected_note)
                            ->visible(fn ($record) => $record->isRejected())
                            ->disabled(),

                    ]),

                Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil')
                    ->color('warning')
                    ->url(fn ($record) => \App\Filament\Resources\Lemburs\LemburResource::getUrl(
                        'edit',
                        ['record' => $record]
                    ))
                    ->visible(fn ($record) => $record->canBeEditedBy(auth()->user())),

                Action::make('approve_atasan')
                    ->label('Approve')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Overtime Request')
                    ->modalDescription(
                        'Are you sure you want to approve this overtime request?'
                    )
                    ->visible(
                        fn ($record) => auth()->user()->isSuperuser()
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

                            $hrds = \App\Models\User::where('level', Role::Admin)
                                ->where('jabatan', Lembur::LEVEL2_JABATAN)
                                ->get();

                            foreach ($hrds as $hrd) {

                                Notification::make()
                                    ->title('Overtime Awaiting HR Approval')
                                    ->body(
                                        "Overtime request from {$record->user->name} is awaiting HR approval."
                                    )
                                    ->sendToDatabase($hrd);
                            }
                        }

                        Notification::make()
                            ->title('Overtime Approved by Manager')
                            ->success()
                            ->sendToDatabase($record->user);

                        Notification::make()
                            ->title('Approval Successful')
                            ->success()
                            ->send();
                    }),

                Action::make('approve_hrd')
                    ->label('Approve (HR)')
                    ->color('success')
                    ->icon('heroicon-o-shield-check')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Overtime Request — HR')
                    ->modalDescription(
                        'Are you sure you want to approve this overtime request?'
                    )
                    ->visible(
                        fn ($record) => auth()->user()->isAdmin()
                            && auth()->user()->jabatan === Lembur::LEVEL2_JABATAN
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
                            ->title('Overtime Approved')
                            ->success()
                            ->sendToDatabase($record->user);

                        Notification::make()
                            ->title('Approval Successful')
                            ->success()
                            ->send();
                    }),

                Action::make('reject')
                    ->label('Reject')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->requiresConfirmation()
                    ->modalHeading('Reject Overtime Request')
                    ->form([

                        Forms\Components\Textarea::make('rejected_note')
                            ->label('Rejection Reason')
                            ->required()
                            ->rows(3),

                    ])
                    ->visible(
                        fn ($record) => $record->canBeApprovedBy(auth()->user())
                    )
                    ->action(function ($record, array $data) {

                        $result = $record->reject(
                            auth()->user(),
                            $data['rejected_note']
                        );

                        if (! $result) {
                            Notification::make()
                                ->title('Rejection Failed')
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Overtime Rejected')
                            ->body("Reason: {$data['rejected_note']}")
                            ->danger()
                            ->sendToDatabase($record->user);

                        Notification::make()
                            ->title('Rejection Successful')
                            ->success()
                            ->send();
                    }),

                Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Cancel Overtime Request')
                    ->modalDescription(
                        'Are you sure you want to cancel this overtime request? This action cannot be undone.'
                    )
                    ->visible(
                        fn ($record) => $record->canBeCancelledBy(auth()->user())
                    )
                    ->action(function ($record) {

                        $result = $record->cancel(auth()->user());

                        if (! $result) {
                            Notification::make()
                                ->title('Cancellation Failed')
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Request Cancelled')
                            ->success()
                            ->send();
                    }),

            ])

            ->bulkActions([

                BulkAction::make('approve')
                    ->label('Approve Selected')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function ($records) {

                        foreach ($records as $record) {

                            if ($record->canBeApprovedBy(auth()->user())) {

                                if ($record->isWaitingAtasan()) {

                                    $record->approveByAtasan(
                                        auth()->user()
                                    );

                                } elseif ($record->isWaitingAdmin()) {

                                    $record->approveByAdmin(
                                        auth()->user()
                                    );
                                }
                            }
                        }

                        Notification::make()
                            ->title('Selected Requests Approved')
                            ->success()
                            ->send();
                    }),

                BulkAction::make('reject')
                    ->label('Reject Selected')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Reject Selected Overtime Requests')
                    ->form([
                        Forms\Components\Textarea::make('rejected_note')
                            ->label('Rejection Reason')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function ($records, array $data): void {
                        $rejectedCount = 0;

                        foreach ($records as $record) {
                            if (! $record->canBeApprovedBy(auth()->user())) {
                                continue;
                            }

                            if ($record->reject(auth()->user(), $data['rejected_note'])) {
                                $rejectedCount++;

                                Notification::make()
                                    ->title('Overtime Rejected')
                                    ->body("Reason: {$data['rejected_note']}")
                                    ->danger()
                                    ->sendToDatabase($record->user);
                            }
                        }

                        Notification::make()
                            ->title($rejectedCount > 0
                                ? "{$rejectedCount} selected request(s) rejected"
                                : 'No selected requests could be rejected')
                            ->{$rejectedCount > 0 ? 'success' : 'warning'}()
                            ->send();
                    }),

            ])

            ->filters([

                SelectFilter::make('bulan_lembur')
                    ->label('Month')
                    ->options(
                        Lembur::query()
                            ->select('bulan_lembur')
                            ->distinct()
                            ->orderBy('bulan_lembur', 'desc')
                            ->pluck('bulan_lembur', 'bulan_lembur')
                            ->toArray()
                    ),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'Pending Approval' => 'Pending',
                        'Approved' => 'Approved',
                        'Rejected' => 'Rejected',
                        'Cancelled' => 'Cancelled',
                    ]),

            ]);
    }
}
