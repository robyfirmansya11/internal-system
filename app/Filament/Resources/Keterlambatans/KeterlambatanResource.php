<?php

namespace App\Filament\Resources\Keterlambatans;

use App\Enums\Role;
use App\Filament\Resources\Keterlambatans\Pages\CreateKeterlambatan;
use App\Filament\Resources\Keterlambatans\Pages\EditKeterlambatan;
use App\Filament\Resources\Keterlambatans\Pages\ListKeterlambatans;
use App\Filament\Resources\Keterlambatans\Schemas\KeterlambatanForm;
use App\Models\Keterlambatan;
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

class KeterlambatanResource extends Resource
{
    protected static ?string $model = Keterlambatan::class;

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Late Working Permits';

    protected static ?string $modelLabel = 'Late Working Permit';

    protected static ?string $pluralModelLabel = 'Late Working Permits';

    protected static string|UnitEnum|null $navigationGroup = 'HRIS';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return KeterlambatanForm::configure($schema);
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
                //     ->searchable()
                //     ->badge()
                //     ->color('info'),

                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('jam_masuk')
                    ->label('Time In')
                    ->time('H:i')
                    ->badge()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('alasan')
                    ->label('Reason')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->alasan),

                // Status badge pakai status langsung dari trait
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'Submitted' => 'gray',
                        'Pending Approval' => 'warning',
                        'Approved' => 'success',
                        'Rejected' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('approval_level')
                    ->label('Stage')
                    ->formatStateUsing(fn ($state, $record) => match (true) {
                        $record->isApproved() => 'Done',
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

            ->headerActions([

                // Export PDF
                Action::make('export_pdf')
                    ->label('Export PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('danger')
                    ->form([

                        Forms\Components\Select::make('user_id')
                            ->label('Employee')
                            ->options(function () {
                                $auth = auth()->user();

                                if ($auth->isUser()) {
                                    return User::where('id', $auth->id)
                                        ->pluck('name', 'id');
                                }

                                if ($auth->isSuperuser()) {
                                    return User::whereHas('departments', function ($q) use ($auth) {
                                        $q->whereIn(
                                            'departments.id',
                                            $auth->departments->pluck('id')
                                        );
                                    })->pluck('name', 'id');
                                }

                                return User::orderBy('name')->pluck('name', 'id');
                            })
                            ->default(fn () => auth()->id())
                            ->disabled(fn () => auth()->user()->isUser())
                            ->searchable()
                            ->native(false),

                        Forms\Components\Select::make('bulan')
                            ->label('Month')
                            ->options([
                                1 => 'January',   2 => 'February',
                                3 => 'March',     4 => 'April',
                                5 => 'May',       6 => 'June',
                                7 => 'July',      8 => 'August',
                                9 => 'September', 10 => 'October',
                                11 => 'November', 12 => 'December',
                            ])
                            ->default(now()->month)
                            ->native(false),

                        Forms\Components\Select::make('tahun')
                            ->label('Year')
                            ->options(
                                collect(range(date('Y') - 5, date('Y')))
                                    ->mapWithKeys(fn ($y) => [$y => $y])
                            )
                            ->default(date('Y'))
                            ->native(false),

                    ])
                    ->action(function ($data, \Livewire\Component $livewire) {
                        $url = route('late-working.report.pdf', $data);
                        $livewire->js("window.open('".addslashes($url)."', '_blank')");
                    }),

            ])

            ->actionsColumnLabel('Action')
            ->actions([

                // EDIT — hanya bisa kalau masih Submitted & milik sendiri
                Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil')
                    ->color('warning')
                    ->url(fn ($record) => static::getUrl('edit', ['record' => $record]))
                    ->visible(fn ($record) => $record->canBeEditedBy(auth()->user())),

                // APPROVE ATASAN (level 1)
                Action::make('approve_atasan')
                    ->label('Approve')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Keterlambatan')
                    ->modalDescription('Yakin ingin menyetujui pengajuan ini?')
                    ->visible(fn ($record) => auth()->user()->isSuperuser()
                        && $record->isWaitingAtasan()
                        && $record->isValidAtasan(auth()->user())
                    )
                    ->action(function ($record) {
                        $approver = auth()->user();
                        $result = $record->approveByAtasan($approver);

                        if (! $result) {
                            Notification::make()
                                ->title('Approval Gagal')
                                ->body('Anda tidak berhak menyetujui pengajuan ini.')
                                ->danger()
                                ->send();

                            return;
                        }

                        // Kalau lanjut ke HRD
                        if ($record->fresh()->isWaitingAdmin()) {
                            $hrds = User::where('level', Role::Admin)
                                ->where('jabatan', Keterlambatan::LEVEL2_JABATAN)
                                ->get();

                            foreach ($hrds as $hrd) {
                                Notification::make()
                                    ->title('New Approval Request')
                                    ->body("Keterlambatan {$record->user->name} menunggu persetujuan HRD.")
                                    ->icon('heroicon-o-clock')
                                    ->sendToDatabase($hrd);
                            }
                        }

                        // Notif ke karyawan
                        Notification::make()
                            ->title('Disetujui Atasan')
                            ->body('Pengajuan keterlambatan Anda telah disetujui atasan.')
                            ->success()
                            ->sendToDatabase($record->user);

                        Notification::make()
                            ->title('Berhasil Approve')
                            ->success()
                            ->send();
                    }),

                // APPROVE HRD (level 2)
                Action::make('approve_hrd')
                    ->label('Approve (HRD)')
                    ->color('success')
                    ->icon('heroicon-o-shield-check')
                    ->requiresConfirmation()
                    ->modalHeading('Approve — HRD')
                    ->modalDescription('Yakin ingin menyetujui pengajuan ini sebagai HRD?')
                    ->visible(fn ($record) => auth()->user()->isAdmin()
                        && auth()->user()->jabatan === Keterlambatan::LEVEL2_JABATAN
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
                            ->title('Approved!')
                            ->body('Pengajuan keterlambatan Anda telah disetujui HRD.')
                            ->success()
                            ->sendToDatabase($record->user);

                        Notification::make()
                            ->title('Berhasil Approve')
                            ->success()
                            ->send();
                    }),

                // REJECT
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
                            ->title('Ditolak')
                            ->body("Alasan: {$data['rejected_note']}")
                            ->danger()
                            ->sendToDatabase($record->user);

                        Notification::make()
                            ->title('Berhasil Reject')
                            ->success()
                            ->send();
                    }),

                // DELETE
                Action::make('delete')
                    ->label('Delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Pengajuan')
                    ->modalDescription('Yakin ingin menghapus pengajuan ini? Tindakan tidak dapat dibatalkan.')
                    ->visible(fn ($record) => $record->canBeDeletedBy(auth()->user()))
                    ->action(function ($record) {
                        $record->delete();

                        Notification::make()
                            ->title('Pengajuan Dihapus')
                            ->success()
                            ->send();
                    }),

                // DETAIL
                Action::make('detail')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading('Detail Keterlambatan')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->form([

                        Forms\Components\TextInput::make('employee')
                            ->label('Karyawan')
                            ->default(fn ($record) => $record->user?->name)
                            ->disabled(),

                        Forms\Components\TextInput::make('department')
                            ->label('Department')
                            ->default(fn ($record) => $record->department?->nama_department)
                            ->disabled(),

                        Forms\Components\DatePicker::make('tanggal')
                            ->label('Tanggal')
                            ->default(fn ($record) => $record->tanggal)
                            ->disabled(),

                        Forms\Components\TimePicker::make('jam_masuk')
                            ->label('Jam Masuk')
                            ->default(fn ($record) => $record->jam_masuk)
                            ->disabled(),

                        Forms\Components\TextInput::make('status')
                            ->label('Status')
                            ->default(fn ($record) => $record->status)
                            ->disabled(),

                        Forms\Components\Textarea::make('alasan')
                            ->label('Alasan')
                            ->rows(3)
                            ->default(fn ($record) => $record->alasan)
                            ->disabled()
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('rejected_note')
                            ->label('Catatan Penolakan')
                            ->rows(3)
                            ->default(fn ($record) => $record->rejected_note)
                            ->disabled()
                            ->visible(fn ($record) => $record->isRejected())
                            ->columnSpanFull(),

                    ]),

            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        return parent::getEloquentQuery()
            ->with(['user', 'department'])
            ->when(
                $user->isUser(),
                fn ($q) => $q->where('user_id', $user->id)
            )
            ->when(
                $user->isSuperuser(),
                fn ($q) => $q->whereHas('user.profile', function ($q) use ($user) {
                    $q->where('atasan_id', $user->id);
                })
            );
        // Admin & Superadmin lihat semua — tidak perlu filter
    }

    public static function canCreate(): bool
    {
        // Superuser tidak bisa buat pengajuan
        return ! auth()->user()->isSuperuser();
    }

    public static function canViewAny(): bool
    {
        return true; // semua role bisa akses
    }

    public static function canEdit($record): bool
    {
        return $record->canBeEditedBy(auth()->user());
    }

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
            'index' => ListKeterlambatans::route('/'),
            'create' => CreateKeterlambatan::route('/create'),
            'edit' => EditKeterlambatan::route('/{record}/edit'),
        ];
    }
}
