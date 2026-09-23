<?php

namespace App\Filament\Resources\RegisterSurats\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class RegisterSuratsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('index')
                    ->label('No.')
                    ->rowIndex()
                    ->alignCenter()
                    ->width('50px'),

                TextColumn::make('no_surat')
                    ->label('Letter Number')
                    ->sortable()
                    ->searchable()
                    ->icon('heroicon-o-document-text')
                    ->iconColor('primary')
                    ->weight(\Filament\Support\Enums\FontWeight::SemiBold)
                    ->copyable()
                    ->copyMessage('Letter number copied!')
                    ->copyMessageDuration(1500),

                TextColumn::make('company.kode')
                    ->label('Company')
                    ->sortable()
                    ->badge()
                    ->color('gray')
                    ->icon('heroicon-o-building-office-2'),

                TextColumn::make('user.name')
                    ->label('Created By')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-user-circle'),

                TextColumn::make('tanggal_surat')
                    ->label('Letter Date')
                    ->date('d M Y')
                    ->sortable()
                    ->icon('heroicon-o-calendar-days')
                    ->iconColor('warning')
                    ->alignCenter(),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->icon('heroicon-o-clock')
                    ->iconColor('gray')
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('keterangan')
                    ->label('Description')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->keterangan)
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->iconColor('gray')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('lampiran_surat')
                    ->label('Attachment')
                    ->alignCenter()
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'View Attachment' : 'No Attachment')
                    ->color(fn ($state) => $state ? 'success' : 'gray')
                    ->action(
                        Action::make('viewAttachment')
                            ->modalHeading('Attachment Preview')
                            ->modalContent(fn ($record) => view(
                                'filament.modals.preview-lampiran',
                                ['record' => $record]
                            ))
                            ->modalSubmitAction(false)
                    ),

            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Edit')
                    ->icon('heroicon-o-pencil-square')
                    ->visible(fn ($record) => auth()->user()->isAdmin()
                        || auth()->user()->isSuperuser()
                        || $record->user_id === auth()->id()
                    ),

                DeleteAction::make()
                    ->label('Delete')
                    ->icon('heroicon-o-trash')
                    ->visible(fn ($record) => $record->user_id === auth()->id()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
