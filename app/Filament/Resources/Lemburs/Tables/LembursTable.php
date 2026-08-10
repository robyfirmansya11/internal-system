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
                    ->suffix(' jam'),

                TextColumn::make('total_month')
                    ->label('Total Month Hours')
                    ->numeric(2)
                    ->suffix(' jam')
                    ->color('warning'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'Pending Approval' => 'warning',
                        'Approved' => 'success',
                        'Rejected' => 'danger',
                        'Cancelled' => 'gray',   // ← tambah
                        default => 'gray',
                    }),

                TextColumn::make('approval_level')
                    ->label('Stage')
                    ->formatStateUsing(fn ($state, $record) => match (true) {
                        $record->isApproved() => 'Approved',
                        $record->isRejected() => 'Rejected',
                        $record->isWaitingAtasan() => 'Pending Manager Review',
                        $record->isWaitingAdmin() => 'Pending HR Approval',
                        $record->isSubmitted() => 'Submitted',
                        default => 'Submitted',   // ⬅️ record yang sudah Cancelled bakal jatuh ke sini, salah label
                    })
                    ->badge()
                    ->color(fn ($state, $record) => match (true) {
                        $record->isCancelled() => 'gray',             // ⬅️ TAMBAHAN
                        $record->isApproved() => 'success',
                        $record->isRejected() => 'danger',
                        $record->isWaitingAtasan() => 'warning',
                        $record->isWaitingAdmin() => 'info',
                        $record->isSubmitted() => 'gray',
                        default => 'gray',
                    }),

            ])

            ->actionsColumnLabel('Action')
            ->actions([

                Action::make('detail')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading('Detail Overtime')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->form([

                        Forms\Components\TextInput::make('employee')
                            ->default(fn ($record) => $record->user->name)
                            ->disabled(),

                        Forms\Components\TextInput::make('department')
                            ->default(fn ($record) => $record->department->nama_department)
                            ->disabled(),

                        Forms\Components\DatePicker::make('tanggal')
                            ->default(fn ($record) => $record->tanggal_lembur)
                            ->disabled(),

                        Forms\Components\TimePicker::make('start')
                            ->default(fn ($record) => $record->mulai_lembur)
                            ->disabled(),

                        Forms\Components\TimePicker::make('finish')
                            ->default(fn ($record) => $record->selesai_lembur)
                            ->disabled(),

                        Forms\Components\TextInput::make('hours')
                            ->default(fn ($record) => $record->jumlah_jam_lembur.' Jam')
                            ->disabled(),

                        Forms\Components\Textarea::make('uraian_pekerjaan')
                            ->default(fn ($record) => $record->uraian_pekerjaan)
                            ->rows(4)
                            ->disabled(),

                        Forms\Components\Textarea::make('reject')
                            ->label('Rejected Note')
                            ->default(fn ($record) => $record->rejected_note)
                            ->visible(fn ($record) => $record->isRejected())
                            ->disabled(),

                    ]),

                Action::make('edit')
                    ->icon('heroicon-o-pencil')
                    ->color('warning')
                    ->url(fn ($record) => \App\Filament\Resources\Lemburs\LemburResource::getUrl('edit', ['record' => $record]))
                    ->visible(fn ($record) => $record->canBeEditedBy(auth()->user())),

                Action::make('approve_atasan')
                    ->label('Approve')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Lembur')
                    ->modalDescription('Yakin ingin menyetujui pengajuan lembur ini?')
                    ->visible(fn ($record) => auth()->user()->isSuperuser()
                        && $record->isWaitingAtasan()
                        && $record->isValidAtasan(auth()->user())
                    )
                    ->action(function ($record) {
                        $result = $record->approveByAtasan(auth()->user());

                        if (! $result) {
                            Notification::make()
                                ->title('Approval Gagal')
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
                                    ->title('Lembur Menunggu Persetujuan HRD')
                                    ->body("Lembur {$record->user->name} menunggu approval HRD.")
                                    ->sendToDatabase($hrd);
                            }
                        }

                        Notification::make()
                            ->title('Lembur Disetujui Atasan')
                            ->success()
                            ->sendToDatabase($record->user);

                        Notification::make()
                            ->title('Berhasil Approve')
                            ->success()
                            ->send();
                    }),

                Action::make('approve_hrd')
                    ->label('Approve (HRD)')
                    ->color('success')
                    ->icon('heroicon-o-shield-check')
                    ->requiresConfirmation()
                    ->modalHeading('Approve — HRD')
                    ->visible(fn ($record) => auth()->user()->isAdmin()
                        && auth()->user()->jabatan === Lembur::LEVEL2_JABATAN
                        && $record->isWaitingAdmin()
                    )
                    ->action(function ($record) {
                        $result = $record->approveByAdmin(auth()->user());

                        if (! $result) {
                            Notification::make()
                                ->title('Approval Gagal')
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Lembur Disetujui!')
                            ->success()
                            ->sendToDatabase($record->user);

                        Notification::make()
                            ->title('Berhasil Approve')
                            ->success()
                            ->send();
                    }),

                Action::make('reject')
                    ->label('Reject')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('rejected_note')
                            ->label('Alasan Penolakan')
                            ->required()
                            ->rows(3),
                    ])
                    ->visible(fn ($record) => $record->canBeApprovedBy(auth()->user()))
                    ->action(function ($record, array $data) {
                        $result = $record->reject(auth()->user(), $data['rejected_note']);

                        if (! $result) {
                            Notification::make()
                                ->title('Reject Gagal')
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Lembur Ditolak')
                            ->body("Alasan: {$data['rejected_note']}")
                            ->danger()
                            ->sendToDatabase($record->user);

                        Notification::make()
                            ->title('Berhasil Reject')
                            ->success()
                            ->send();
                    }),

                // HAPUS action delete lama, ganti dengan ini:
                Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Batalkan Pengajuan')
                    ->modalDescription('Yakin ingin membatalkan pengajuan lembur ini? Tindakan ini tidak dapat dibatalkan.')
                    ->visible(fn ($record) => $record->canBeCancelledBy(auth()->user()))
                    ->action(function ($record) {
                        $result = $record->cancel(auth()->user());

                        if (! $result) {
                            Notification::make()
                                ->title('Gagal Membatalkan')
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Pengajuan Dibatalkan')
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
                                    $record->approveByAtasan(auth()->user());
                                } elseif ($record->isWaitingAdmin()) {
                                    $record->approveByAdmin(auth()->user());
                                }

                            }

                        }

                        Notification::make()
                            ->title('Selected requests approved')
                            ->success()
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
                    ->options([
                        'Pending Approval' => 'Pending',
                        'Approved' => 'Approved',
                        'Rejected' => 'Rejected',
                    ]),

            ]);
    }
}
