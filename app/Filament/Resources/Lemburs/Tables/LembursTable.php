<?php

namespace App\Filament\Resources\Lemburs\Tables;

use App\Enums\Role;
use App\Models\Lembur;
use Filament\Actions\Action;
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

                TextColumn::make('department.nama_department')
                    ->label('Department')
                    ->badge()
                    ->color('info'),

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
                    ->label('Total Bulan Ini')
                    ->numeric(2)
                    ->suffix(' jam')
                    ->color('warning'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'Pending Approval' => 'warning',
                        'Approved' => 'success',
                        'Rejected' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('approval_level')
                    ->label('Stage')
                    ->formatStateUsing(fn ($state, $record) => match (true) {
                        $record->isApproved() => 'Approved',
                        $record->isRejected() => 'Rejected',
                        $record->isWaitingAtasan() => 'Waiting Atasan',
                        $record->isWaitingAdmin() => 'Waiting HRD',
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

                Action::make('edit')
                    ->label('Edit')
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
                    ->visible(fn ($record) => $record->canBeApprovedBy(auth()->user())
                    )
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

                Action::make('delete')
                    ->label('Delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->canBeDeletedBy(auth()->user()))
                    ->action(function ($record) {
                        $record->delete();

                        Notification::make()
                            ->title('Lembur Dihapus')
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
