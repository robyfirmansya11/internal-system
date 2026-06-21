<?php

namespace App\Filament\Resources\PermohonanStempels;

use App\Filament\Resources\PermohonanStempels\Pages\CreatePermohonanStempel;
use App\Filament\Resources\PermohonanStempels\Pages\EditPermohonanStempel;
use App\Filament\Resources\PermohonanStempels\Pages\ListPermohonanStempels;
use App\Filament\Resources\PermohonanStempels\Schemas\PermohonanStempelForm;
use App\Models\PermohonanStempel;
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

use function asset;
use function auth;
use function filled;
use function route;

class PermohonanStempelResource extends Resource
{
    protected static ?string $model = PermohonanStempel::class;

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Stamp Application Letter';

    protected static ?string $modelLabel = 'Stamp Application Letter';

    protected static ?string $pluralModelLabel = 'Stamp Application Letters';

    protected static string|UnitEnum|null $navigationGroup = 'Legal & Litigation';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $recordTitleAttribute = 'nomor_surat';

    public static function form(Schema $schema): Schema
    {
        return PermohonanStempelForm::configure($schema);
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

                Tables\Columns\TextColumn::make('company.nama')
                    ->label('Company')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('nomor_surat')
                    ->label('Letter Number')
                    ->searchable(),

                Tables\Columns\TextColumn::make('tujuan')
                    ->label('Tujuan')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->tujuan),

                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),

                /**
                 * Status dari trait — kapital.
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

                Tables\Columns\TextColumn::make('approval_level')
                    ->label('Stage')
                    ->formatStateUsing(fn ($state, $record) => match (true) {
                        $record->isApproved() => 'Approved',
                        $record->isRejected() => 'Rejected',
                        $record->isWaitingAtasan() => 'Waiting Atasan',
                        default => 'Draft',
                    })
                    ->badge()
                    ->color(fn ($state, $record) => match (true) {
                        $record->isApproved() => 'success',
                        $record->isRejected() => 'danger',
                        $record->isWaitingAtasan() => 'warning',
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
                 * APPROVE — hanya Superuser yang merupakan atasan langsung.
                 * Permohonan Stempel hanya 1 level approval.
                 */
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Permohonan Stempel')
                    ->modalDescription('Yakin ingin menyetujui permohonan stempel ini?')
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

                        Notification::make()
                            ->title('Permohonan Stempel Disetujui')
                            ->success()
                            ->sendToDatabase($record->user);

                        Notification::make()
                            ->title('Berhasil Approve')
                            ->success()
                            ->send();
                    }),

                /**
                 * REJECT — hanya atasan langsung yang bisa reject.
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
                            ->title('Permohonan Stempel Ditolak')
                            ->body("Alasan: {$data['rejected_note']}")
                            ->danger()
                            ->sendToDatabase($record->user);

                        Notification::make()
                            ->title('Berhasil Reject')
                            ->success()
                            ->send();
                    }),

                /**
                 * DELETE — hanya kalau masih Submitted & milik sendiri.
                 */
                Action::make('delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->canBeDeletedBy(auth()->user()))
                    ->action(function ($record) {
                        $record->delete();

                        Notification::make()
                            ->title('Permohonan Dihapus')
                            ->success()
                            ->send();
                    }),

                /**
                 * DETAIL — tampilkan informasi lengkap dalam modal.
                 */
                Action::make('detail')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading('Detail Permohonan Stempel')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->form([
                        Forms\Components\TextInput::make('employee')
                            ->label('Employee')
                            ->default(fn ($record) => $record->user?->name)
                            ->disabled(),

                        Forms\Components\TextInput::make('department')
                            ->label('Department')
                            ->default(fn ($record) => $record->department?->nama_department)
                            ->disabled(),

                        Forms\Components\TextInput::make('company')
                            ->label('Company')
                            ->default(fn ($record) => $record->company?->nama)
                            ->disabled(),

                        Forms\Components\TextInput::make('nomor_surat')
                            ->label('Nomor Surat')
                            ->default(fn ($record) => $record->nomor_surat)
                            ->disabled(),

                        Forms\Components\TextInput::make('tujuan')
                            ->label('Tujuan')
                            ->default(fn ($record) => $record->tujuan)
                            ->disabled(),

                        Forms\Components\DatePicker::make('tanggal')
                            ->label('Tanggal Pengajuan')
                            ->default(fn ($record) => $record->tanggal)
                            ->disabled(),

                        Forms\Components\DatePicker::make('tanggal_surat')
                            ->label('Tanggal Surat')
                            ->default(fn ($record) => $record->tanggal_surat)
                            ->disabled(),

                        Forms\Components\DatePicker::make('tanggal_stempel')
                            ->label('Tanggal Stempel')
                            ->default(fn ($record) => $record->tanggal_stempel)
                            ->disabled(),

                        Forms\Components\TextInput::make('ditandatangani_oleh')
                            ->label('Ditandatangani Oleh')
                            ->default(fn ($record) => $record->ditandatangani_oleh)
                            ->disabled(),

                        Forms\Components\Textarea::make('keterangan')
                            ->label('Keterangan')
                            ->default(fn ($record) => $record->keterangan)
                            ->disabled()
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('status')
                            ->label('Status')
                            ->default(fn ($record) => $record->status)
                            ->disabled(),

                        Forms\Components\Textarea::make('rejected_note')
                            ->label('Alasan Penolakan')
                            ->default(fn ($record) => $record->rejected_note)
                            ->disabled()
                            ->visible(fn ($record) => $record->isRejected())
                            ->columnSpanFull(),
                    ]),

                /**
                 * VIEW LAMPIRAN — buka file lampiran di tab baru.
                 * Hanya muncul kalau lampiran ada.
                 */
                Action::make('lampiran')
                    ->label('Attachment')
                    ->icon('heroicon-o-paper-clip')
                    ->color('gray')
                    ->url(fn ($record) => $record->lampiran
                        ? asset('storage/'.$record->lampiran)
                        : null
                    )
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => filled($record->lampiran)),

                /**
                 * PRINT PDF.
                 */
                Action::make('print')
                    ->label('Print PDF')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn ($record) => route('permohonan-stempel.print', $record->id))
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
            ->with(['user', 'department', 'company'])
            ->when(
                $user->isUser(),
                fn ($q) => $q->where('user_id', $user->id)
            )
            ->when(
                $user->isSuperuser(),
                // Fix: filter by atasan_id bukan department_id
                fn ($q) => $q->whereHas('user.profile', function ($q) use ($user) {
                    $q->where('atasan_id', $user->id);
                })
            );
        // Admin & Superadmin lihat semua
    }

    /*
    |--------------------------------------------------------------------------
    | PERMISSIONS
    |--------------------------------------------------------------------------
    */

    public static function canViewAny(): bool
    {
        return true;
    }

    /**
     * Superuser tidak bisa buat permohonan stempel untuk dirinya sendiri.
     */
    public static function canCreate(): bool
    {
        return ! auth()->user()->isSuperuser();
    }

    public static function canEdit($record): bool
    {
        return $record->canBeEditedBy(auth()->user());
    }

    public static function canDelete($record): bool
    {
        return $record->canBeDeletedBy(auth()->user());
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPermohonanStempels::route('/'),
            'create' => CreatePermohonanStempel::route('/create'),
            'edit' => EditPermohonanStempel::route('/{record}/edit'),
        ];
    }
}
