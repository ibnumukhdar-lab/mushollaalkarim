<?php

namespace App\Filament\Resources\Penilaians\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PenilaiansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('santri_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('ustadz_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('tanggal')
                    ->date()
                    ->sortable(),
                TextColumn::make('jenis')
                    ->badge(),
                TextColumn::make('kategori')
                    ->searchable(),
                TextColumn::make('nilai_1')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('nilai_2')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('nilai_3')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('ustadz_nama')
                    ->searchable(),
                TextColumn::make('juz')
                    ->searchable(),
                TextColumn::make('surah')
                    ->searchable(),
                TextColumn::make('ayat')
                    ->searchable(),
                TextColumn::make('iqro_jilid')
                    ->searchable(),
                TextColumn::make('iqro_halaman')
                    ->searchable(),
                TextColumn::make('nilai')
                    ->searchable(),
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
