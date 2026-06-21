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
                    ->label('Karyawan')
                    ->searchable()
                    ->sortable(),

                // TextColumn::make('department.nama_department')
                //     ->label('Department')
                //     ->badge()
                //     ->color('info'),

                // TextColumn::make('company.nama')
                //     ->label('Perusahaan')
                //     ->badge()
                //     ->color('gray'),

                TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->keterangan),

                TextColumn::make('total')
                    ->label('Total')
                    ->money('IDR'),

                /**
                 * Status badge — menggunakan status dari HasApprovalWorkflow trait.
                 */
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'Pending Approval' => 'warning',
                        'Approved' => 'success',
                        'Rejected' => 'danger',
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
                        $record->isWaitingAtasan() => 'Waiting Manager',

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

                /**
                 * APPROVE ATASAN (level 1).
                 * Hanya Superuser yang merupakan atasan langsung karyawan.
                 */
                Action::make('approve_atasan')
                    ->label('Approve')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Perjalanan Dinas')
                    ->modalDescription('Yakin ingin menyetujui pengajuan ini?')
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

                        if ($record->isApproved()) {

                            Notification::make()
                                ->title('Perjalanan Dinas Disetujui')
                                ->body('Pengajuan telah selesai disetujui.')
                                ->success()
                                ->sendToDatabase($record->user);

                        } elseif ($record->isWaitingAdmin()) {

                            $fms = \App\Models\User::query()
                                ->where('jabatan', PerjalananDinas::LEVEL2_JABATAN)
                                ->whereKeyNot(auth()->id()) // jangan kirim ke approver yang baru saja approve
                                ->get();

                            foreach ($fms as $fm) {

                                Notification::make()
                                    ->title('Perjalanan Dinas Menunggu Persetujuan Finance')
                                    ->body("Perjalanan dinas {$record->user->name} menunggu approval Finance Manager.")
                                    ->sendToDatabase($fm);
                            }

                            Notification::make()
                                ->title('Disetujui Atasan')
                                ->body('Pengajuan telah diteruskan ke Finance Manager.')
                                ->success()
                                ->sendToDatabase($record->user);
                        }

                        Notification::make()
                            ->title('Berhasil Approve')
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
                                ->title('Approval Gagal')
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Perjalanan Dinas Disetujui!')
                            ->success()
                            ->sendToDatabase($record->user);

                        Notification::make()
                            ->title('Berhasil Approve')
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
                            ->title('Ditolak')
                            ->body("Alasan: {$data['rejected_note']}")
                            ->danger()
                            ->sendToDatabase($record->user);

                        Notification::make()
                            ->title('Berhasil Reject')
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
