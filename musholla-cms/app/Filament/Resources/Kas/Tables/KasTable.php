<?php

namespace App\Filament\Resources\Kas\Tables;

use App\Models\Kas;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class KasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('tanggal', 'desc')
            ->columns([
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('jenis')
                    ->label('Jenis')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'masuk' ? 'Masuk' : 'Keluar')
                    ->color(fn ($state) => $state === 'masuk' ? 'success' : 'danger'),
                TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->wrap()
                    ->limit(60)
                    ->searchable(),
                TextColumn::make('kategori')
                    ->label('Kategori')
                    ->badge()
                    ->color('gray')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('jumlah')
                    ->label('Jumlah')
                    ->alignEnd()
                    ->formatStateUsing(fn ($state, Kas $record) => ($record->jenis === 'masuk' ? '+' : '−').' Rp '.number_format((float) $state, 0, ',', '.'))
                    ->color(fn (Kas $record) => $record->jenis === 'masuk' ? 'success' : 'danger')
                    ->sortable(),
                TextColumn::make('pencatat.name')
                    ->label('Dicatat oleh')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('jenis')
                    ->label('Jenis')
                    ->options(['masuk' => 'Uang masuk', 'keluar' => 'Uang keluar']),
                SelectFilter::make('kategori')
                    ->label('Kategori')
                    ->options(fn () => Kas::query()
                        ->whereNotNull('kategori')
                        ->where('kategori', '!=', '')
                        ->distinct()
                        ->orderBy('kategori')
                        ->pluck('kategori', 'kategori')
                        ->all()),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
