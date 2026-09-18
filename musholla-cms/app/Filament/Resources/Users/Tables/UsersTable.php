<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Surel')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('peran')
                    ->label('Peran')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'admin' => 'Admin',
                        'anggota' => 'Anggota',
                        default => ucfirst((string) $state),
                    })
                    ->color(fn ($state) => match ($state) {
                        'admin' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('no_wa')
                    ->label('WhatsApp')
                    ->placeholder('—')
                    ->searchable(),
                IconColumn::make('aktif')
                    ->label('Aktif')
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('peran')
                    ->label('Peran')
                    ->options([
                        'admin' => 'Admin',
                        'anggota' => 'Anggota',
                    ]),
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
