<?php

namespace App\Filament\Resources\SuratPerintahBayars\Tables;

use App\Models\SuratPerintahBayar;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class SuratPerintahBayarsTable
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

                TextColumn::make('company.kode')
                    ->label('Company')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('no_invoice')
                    ->label('Invoice')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('tanggal_penagihan')
                    ->label('Billing Date')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('tanggal_jatuhtempo')
                    ->label('Due Date')
                    ->date('d M Y'),

                TextColumn::make('jumlah_total')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable(),

                /**
                 * Status badge menggunakan nilai dari trait (kapital).
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
                 * Stage menunjukkan posisi dalam alur approval.
                 */
                TextColumn::make('approval_level')
                    ->label('Stage')
                    ->formatStateUsing(fn ($state, $record) => match (true) {
                        $record->isApproved() => 'Approved',
                        $record->isRejected() => 'Rejected',
                        $record->isWaitingAtasan() => 'Waiting Manager',
                        $record->isWaitingAdmin() => 'Waiting Finance',
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

            ->actionsColumnLabel('Action')
            ->actions([

                /**
                 * EDIT — hanya kalau masih Submitted & milik sendiri.
                 */
                Action::make('edit')
                    ->icon('heroicon-o-pencil')
                    ->color('warning')
                    ->url(fn ($record) => route('filament.admin.resources.surat-perintah-bayars.edit', $record)
                    )
                    ->visible(fn ($record) => $record->canBeEditedBy(auth()->user())),

                /**
                 * APPROVE ATASAN (level 1).
                 * Hanya Superuser yang merupakan atasan langsung karyawan.
                 */
                Action::make('approve_atasan')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve SPB')
                    ->modalDescription('Are you sure approve this request ?')
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
                            // Atasan sekaligus FM → langsung Approved
                            Notification::make()
                                ->title('SPB Disetujui')
                                ->success()
                                ->sendToDatabase($record->user);

                        } elseif ($record->isWaitingAdmin()) {
                            // Lanjut ke Finance Manager
                            $fms = User::where('jabatan', SuratPerintahBayar::LEVEL2_JABATAN)
                                ->whereKeyNot(auth()->id())
                                ->get();

                            foreach ($fms as $fm) {
                                Notification::make()
                                    ->title('SPB Menunggu Persetujuan Finance')
                                    ->body("SPB {$record->user->name} menunggu approval Finance Manager.")
                                    ->sendToDatabase($fm);
                            }

                            Notification::make()
                                ->title('Disetujui Atasan')
                                ->body('SPB telah diteruskan ke Finance Manager.')
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
                    ->icon('heroicon-o-shield-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve — Finance Manager')
                    ->visible(fn ($record) => auth()->user()->jabatan === SuratPerintahBayar::LEVEL2_JABATAN
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
                            ->title('SPB Disetujui!')
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
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
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
                            ->title('SPB Ditolak')
                            ->body("Alasan: {$data['rejected_note']}")
                            ->danger()
                            ->sendToDatabase($record->user);

                        Notification::make()
                            ->title('Berhasil Reject')
                            ->success()
                            ->send();
                    }),

                /**
                 * DELETE — hanya kalau belum Approved & milik sendiri.
                 */
                Action::make('delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->canBeDeletedBy(auth()->user()))
                    ->action(function ($record) {
                        $record->delete();

                        Notification::make()
                            ->title('SPB Dihapus')
                            ->success()
                            ->send();
                    }),

                /**
                 * DETAIL — tampilkan informasi lengkap dalam modal.
                 */
                Action::make('detail')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading('Detail SPB')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->infolist([
                        TextEntry::make('user.name')
                            ->label('Employee'),
                        TextEntry::make('department.nama_department')
                            ->label('Department'),
                        TextEntry::make('company.nama')
                            ->label('Company'),
                        TextEntry::make('no_invoice')
                            ->label('No. Invoice'),
                        TextEntry::make('tanggal_penagihan')
                            ->label('Tanggal Penagihan')
                            ->date('d M Y'),
                        TextEntry::make('tanggal_jatuhtempo')
                            ->label('Jatuh Tempo')
                            ->date('d M Y'),
                        TextEntry::make('jumlah')
                            ->label('Jumlah')
                            ->money('IDR'),
                        TextEntry::make('ppn')
                            ->label('PPN')
                            ->money('IDR'),
                        TextEntry::make('pph')
                            ->label('PPH')
                            ->money('IDR'),
                        TextEntry::make('admin')
                            ->label('Admin')
                            ->money('IDR'),
                        TextEntry::make('jumlah_total')
                            ->label('Total')
                            ->money('IDR'),
                        TextEntry::make('terbilang')
                            ->label('Terbilang')
                            ->columnSpanFull(),
                        TextEntry::make('pembayaran_tahap')
                            ->label('Tahap Pembayaran'),
                        TextEntry::make('jumlah_lampiran')
                            ->label('Jumlah Lampiran'),
                        TextEntry::make('keterangan')
                            ->label('Keterangan')
                            ->columnSpanFull(),
                        TextEntry::make('informasi_transfer')
                            ->label('Informasi Transfer')
                            ->columnSpanFull(),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn (string $state) => match ($state) {
                                'Pending Approval' => 'warning',
                                'Approved' => 'success',
                                'Rejected' => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('approver.name')
                            ->label('Disetujui Oleh')
                            ->placeholder('-'),
                        TextEntry::make('rejected_note')
                            ->label('Alasan Penolakan')
                            ->visible(fn ($record) => $record->isRejected())
                            ->columnSpanFull(),
                        TextEntry::make('lampiran')
                            ->label('Lampiran')
                            ->formatStateUsing(fn ($state) => $state ? basename($state) : '-')
                            ->url(fn ($record) => $record->lampiran
                                ? asset('storage/'.$record->lampiran)
                                : null
                            )
                            ->openUrlInNewTab(),
                    ]),

                /**
                 * PRINT PDF — buka PDF di tab baru.
                 */
                Action::make('print')
                    ->label('Print PDF')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->url(fn ($record) => route('spb.pdf', $record->id))
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
