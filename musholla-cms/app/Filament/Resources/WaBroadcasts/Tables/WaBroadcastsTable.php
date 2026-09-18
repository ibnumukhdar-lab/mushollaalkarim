<?php

namespace App\Filament\Resources\WaBroadcasts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WaBroadcastsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('wa_template_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('pesan_terkirim')
                    ->searchable(),
                TextColumn::make('jumlah_target')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('terkirim')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('gagal')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('mulai_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('selesai_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('status')
                    ->searchable(),
                TextColumn::make('oleh_user_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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
