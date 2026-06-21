<?php

namespace App\Filament\Resources\Kasbons;

use App\Enums\Role;
use App\Filament\Resources\Kasbons\Pages\CreateKasbon;
use App\Filament\Resources\Kasbons\Pages\EditKasbon;
use App\Filament\Resources\Kasbons\Pages\ListKasbons;
use App\Filament\Resources\Kasbons\Schemas\KasbonForm;
use App\Models\Kasbon;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class KasbonResource extends Resource
{
    protected static ?string $model = Kasbon::class;

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Loan Note';

    protected static ?string $modelLabel = 'Loan Note';

    protected static ?string $pluralModelLabel = 'Loan Notes';

    protected static string|UnitEnum|null $navigationGroup = 'Finance, Accounting & Tax';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $recordTitleAttribute = 'tanggal';

    public static function form(Schema $schema): Schema
    {
        return KasbonForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([

                Tables\Columns\TextColumn::make('index')
                    ->label('No.')
                    ->rowIndex(),

                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),

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

                Tables\Columns\TextColumn::make('keterangan')
                    ->label('Description')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->keterangan),

                Tables\Columns\TextColumn::make('jumlah_dana')
                    ->label('Total Amount')
                    ->money('IDR')
                    ->sortable(),

                /**
                 * Status badge menggunakan nilai dari trait (kapital).
                 */
                Tables\Columns\TextColumn::make('status')
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
                Tables\Columns\TextColumn::make('approval_level')
                    ->label('Stage')
                    ->formatStateUsing(fn ($state, $record) => match (true) {
                        $record->isApproved() => 'Approved',
                        $record->isRejected() => 'Rejected',
                        $record->isWaitingAtasan() => 'Waiting Atasan',
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
                 * APPROVE ATASAN (level 1).
                 * Hanya Superuser yang merupakan atasan langsung karyawan.
                 */
                Action::make('approve_atasan')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Kasbon')
                    ->modalDescription('Yakin ingin menyetujui pengajuan kasbon ini?')
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
                                ->title('Kasbon Disetujui')
                                ->body('Kasbon Anda telah disetujui.')
                                ->success()
                                ->sendToDatabase($record->user);

                        } elseif ($record->isWaitingAdmin()) {
                            // Lanjut ke Finance Manager
                            $fms = User::where('jabatan', Kasbon::LEVEL2_JABATAN)
                                ->whereKeyNot(auth()->id())
                                ->get();

                            foreach ($fms as $fm) {
                                Notification::make()
                                    ->title('Kasbon Menunggu Persetujuan Finance')
                                    ->body("Kasbon {$record->user->name} menunggu approval Finance Manager.")
                                    ->sendToDatabase($fm);
                            }

                            Notification::make()
                                ->title('Disetujui Atasan')
                                ->body('Kasbon Anda telah diteruskan ke Finance Manager.')
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
                    ->visible(fn ($record) => auth()->user()->jabatan === Kasbon::LEVEL2_JABATAN
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
                            ->title('Kasbon Disetujui!')
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
                            ->title('Kasbon Ditolak')
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
                            ->title('Kasbon Dihapus')
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
                    ->modalHeading('Detail Kasbon')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->infolist([
                        TextEntry::make('user.name')
                            ->label('Employee'),
                        TextEntry::make('department.nama_department')
                            ->label('Department'),
                        TextEntry::make('company.nama')
                            ->label('Company'),
                        TextEntry::make('tanggal')
                            ->label('Tanggal')
                            ->date('d M Y'),
                        TextEntry::make('jumlah_dana')
                            ->label('Jumlah Dana')
                            ->money('IDR'),
                        TextEntry::make('terbilang')
                            ->label('Terbilang'),
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
                    ->url(fn ($record) => route('kasbon.pdf', $record->id))
                    ->openUrlInNewTab(),

            ]);
    }

    /**
     * Query utama dengan eager loading dan filter berdasarkan role.
     *
     * - User      → hanya miliknya sendiri
     * - Superuser → hanya bawahan langsung (via atasan_id)
     *   Kalau juga Finance Manager → tambah lihat level 2
     * - Admin & Superadmin → semua data
     */
    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        $query = parent::getEloquentQuery()
            ->with(['user', 'department', 'company']);

        if ($user->isUser()) {
            return $query->where('user_id', $user->id);
        }

        if ($user->isSuperuser() && $user->jabatan === Kasbon::LEVEL2_JABATAN) {
            // Finance Manager Superuser: lihat bawahan DAN semua level 2
            return $query->where(function ($q) use ($user) {
                $q->whereHas('user.profile', function ($q2) use ($user) {
                    $q2->where('atasan_id', $user->id);
                })
                    ->orWhere(function ($q3) {
                        $q3->where('approval_level', 2)
                            ->where('status', 'Pending Approval');
                    });
            });
        }

        if ($user->isSuperuser()) {
            // Superuser biasa: hanya bawahan langsung
            return $query->whereHas('user.profile', function ($q) use ($user) {
                $q->where('atasan_id', $user->id);
            });
        }

        // Admin & Superadmin lihat semua
        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | PERMISSIONS
    |--------------------------------------------------------------------------
    */

    /** Semua role bisa akses halaman ini */
    public static function canViewAny(): bool
    {
        return true;
    }

    /** Superuser tidak bisa buat kasbon untuk dirinya sendiri */
    public static function canCreate(): bool
    {
        return ! auth()->user()->isSuperuser();
    }

    /** Hanya bisa edit kalau masih Submitted & milik sendiri */
    public static function canEdit($record): bool
    {
        return $record->canBeEditedBy(auth()->user());
    }

    /** Hanya bisa delete kalau belum Approved & milik sendiri */
    public static function canDelete($record): bool
    {
        return $record->canBeDeletedBy(auth()->user());
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKasbons::route('/'),
            'create' => CreateKasbon::route('/create'),
            'edit' => EditKasbon::route('/{record}/edit'),
        ];
    }
}
