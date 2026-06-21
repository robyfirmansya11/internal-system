<?php

namespace App\Filament\Resources\Kasbons\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

use App\Enums\Role;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables;

class KasbonsTable
{
    public static function configure(Table $table): Table
    {
        return $table

            ->columns([

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Employee')
                    ->searchable(),

                Tables\Columns\TextColumn::make('department.nama_department')
                    ->label('Department'),

                Tables\Columns\TextColumn::make('company.nama')
                    ->label('Company'),

                Tables\Columns\TextColumn::make('jumlah_dana')
                    ->label('Jumlah Dana')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger'  => 'rejected',
                    ]),

            ])

            ->actionsColumnLabel('Action')

            ->actions([

                /*
                |--------------------------------------------------------------------------
                | EDIT
                |--------------------------------------------------------------------------
                */

                Action::make('edit')
                    ->icon('heroicon-o-pencil')
                    ->color('warning')
                    ->url(fn ($record) => \App\Filament\Resources\Kasbons\KasbonResource::getUrl('edit', ['record' => $record]))
                    ->visible(fn ($record) =>
                        auth()->user()->level === Role::User
                        && $record->user_id === auth()->id()
                        && $record->status === 'pending'
                    ),

                /*
                |--------------------------------------------------------------------------
                | APPROVE
                |--------------------------------------------------------------------------
                */

                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) =>
                        auth()->user()->level === Role::Superuser
                        && $record->status === 'pending'
                    )
                    ->action(function ($record) {

                        $record->update([
                            'status'      => 'approved',
                            'approved_by' => auth()->id(),
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Kasbon Disetujui')
                            ->success()
                            ->sendToDatabase($record->user);
                    }),

                /*
                |--------------------------------------------------------------------------
                | REJECT
                |--------------------------------------------------------------------------
                */

                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn ($record) =>
                        auth()->user()->level === Role::Superuser
                        && $record->status === 'pending'
                    )
                    ->action(function ($record) {

                        $record->update([
                            'status'      => 'rejected',
                            'approved_by' => auth()->id(),
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Kasbon Ditolak')
                            ->danger()
                            ->sendToDatabase($record->user);
                    }),

                /*
                |--------------------------------------------------------------------------
                | DELETE
                |--------------------------------------------------------------------------
                */

                Action::make('delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn ($record) =>
                        auth()->user()->level === Role::User
                        && $record->user_id === auth()->id()
                        && $record->status === 'pending'
                    )
                    ->action(fn ($record) => $record->delete()),

                /*
                |--------------------------------------------------------------------------
                | DETAIL
                |--------------------------------------------------------------------------
                */

                Action::make('detail')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->infolist([
                        TextEntry::make('user.name')->label('Employee'),
                        TextEntry::make('department.nama_department')->label('Department'),
                        TextEntry::make('company.nama')->label('Company'),
                        TextEntry::make('tanggal')->date()->label('Tanggal'),
                        TextEntry::make('jumlah_dana')
                            ->label('Jumlah Dana')
                            ->money('IDR'),
                        TextEntry::make('terbilang')->label('Terbilang'),
                        TextEntry::make('keterangan')->label('Keterangan'),
                        TextEntry::make('informasi_transfer')->label('Informasi Transfer'),
                        TextEntry::make('lampiran')
                            ->label('Lampiran')
                            ->formatStateUsing(fn ($state) => $state ? basename($state) : '-')
                            ->url(fn ($record) => $record->lampiran ? asset('storage/' . $record->lampiran) : null)
                            ->openUrlInNewTab(),
                        TextEntry::make('status'),
                        TextEntry::make('approver.name')->label('Disetujui Oleh'),
                        TextEntry::make('created_at')->dateTime(),
                    ]),

            ]);
    }
}
