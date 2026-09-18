<?php

namespace App\Filament\Resources\Santris\Tables;

use App\Models\Santri;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class SantrisTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('nama')
                    ->label('Nama')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('jenis_kelamin')
                    ->label('L/P')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'P' ? 'P' : 'L')
                    ->color(fn ($state) => $state === 'P' ? 'info' : 'gray'),
                TextColumn::make('kelas_sekolah')
                    ->label('Kelas')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('tempat_lahir')
                    ->label('Tempat lahir')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('tanggal_lahir')
                    ->label('Tanggal lahir')
                    ->date('d M Y')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('ustadz.nama')
                    ->label('Ustadz pembina')
                    ->placeholder('—')
                    ->toggleable(),
                IconColumn::make('aktif')
                    ->label('Aktif')
                    ->boolean(),
                TextColumn::make('catatan')
                    ->label('Catatan')
                    ->wrap()
                    ->limit(60)
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->filters([
                TernaryFilter::make('aktif')
                    ->label('Status keaktifan')
                    ->placeholder('Semua')
                    ->trueLabel('Sudah aktif')
                    ->falseLabel('Menunggu pemeriksaan'),
            ])
            ->recordActions([
                Action::make('aktifkan')
                    ->label('Aktifkan')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (Santri $r) => ! $r->aktif)
                    ->requiresConfirmation()
                    ->modalHeading('Aktifkan santri ini?')
                    ->modalDescription('Data hasil pendaftaran akan ditandai aktif. Orang tua dapat diberi tahu melalui WhatsApp.')
                    ->action(function (Santri $r) {
                        $r->aktif = true;
                        $r->save();
                        Notification::make()->title($r->nama.' diaktifkan')->success()->send();
                    }),
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
