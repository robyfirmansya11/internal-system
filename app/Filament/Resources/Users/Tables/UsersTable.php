<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\Role;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(25) // default 25, jangan terlalu banyak
            ->paginationPageOptions([10, 25, 50])
            ->columns([

                TextColumn::make('index')
                    ->label('No.')
                    ->rowIndex(),

                ImageColumn::make('profile.foto')
                    ->label('Foto')
                    ->disk('public')
                    ->circular()
                    ->size(45),

                TextColumn::make('profile.nik')
                    ->label('NIK')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),

                TextColumn::make('department')
                    ->label('Department')
                    ->badge()
                    ->color('info')
                    ->searchable(false), // accessor, tidak bisa searchable langsung

                TextColumn::make('jabatan')
                    ->label('Jabatan')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable(),

                // Fix: atasan via relasi, bukan query manual
                TextColumn::make('profile.atasan.name')
                    ->label('Atasan')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('profile.status_karyawan')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Tetap' => 'success',
                        'Kontrak' => 'warning',
                        'Magang' => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('profile.lokasi_kerja')
                    ->label('Lokasi Kerja')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('profile.no_hp')
                    ->label('No HP')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('profile.tanggal_masuk')
                    ->label('Tanggal Masuk')
                    ->date('d M Y')
                    ->sortable(),

                // Fix: gunakan Role enum value yang benar (kapital)
                TextColumn::make('level')
                    ->label('Role')
                    ->badge()
                    ->formatStateUsing(fn (Role $state) => $state->label())
                    ->color(fn (Role $state) => match ($state) {
                        Role::Superadmin => 'danger',
                        Role::Admin => 'warning',
                        Role::Superuser => 'info',
                        Role::User => 'success',
                    }),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),

            ])
            ->filters([

                SelectFilter::make('level')
                    ->label('Role')
                    ->options(
                        collect(Role::cases())
                            ->mapWithKeys(fn (Role $r) => [
                                $r->value => $r->label(),
                            ])
                    ),
                SelectFilter::make('status_karyawan')
                    ->label('Status Karyawan')
                    ->options([
                        'Tetap' => 'Tetap',
                        'Kontrak' => 'Kontrak',
                        'Magang' => 'Magang',
                    ])
                    ->query(function ($query, array $data) {
                        if (blank($data['value'])) {
                            return $query;
                        }

                        return $query->whereHas('profile', function ($q) use ($data) {
                            $q->where('status_karyawan', $data['value']);
                        });
                    }),

                SelectFilter::make('departments')
                    ->label('Department')
                    ->relationship('departments', 'nama_department')
                    ->preload(),

            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
