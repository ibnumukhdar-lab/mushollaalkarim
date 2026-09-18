<?php

namespace App\Filament\Resources\Infaqs\Tables;

use App\Models\Infaq;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class InfaqsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('nama_donatur')
                    ->label('Donatur')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('no_wa')
                    ->label('WhatsApp')
                    ->copyable()
                    ->searchable(),
                TextColumn::make('nominal')
                    ->label('Nominal')
                    ->alignEnd()
                    ->formatStateUsing(fn ($state) => 'Rp '.number_format((float) $state, 0, ',', '.'))
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'terverifikasi' => 'Terverifikasi',
                        'ditolak' => 'Ditolak',
                        default => 'Menunggu',
                    })
                    ->color(fn ($state) => match ($state) {
                        'terverifikasi' => 'success',
                        'ditolak' => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('tujuan')
                    ->label('Untuk')
                    ->placeholder('—')
                    ->toggleable(),
                ImageColumn::make('bukti_path')
                    ->label('Bukti')
                    ->disk('public')
                    ->height(38)
                    ->toggleable(),
                TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->wrap()
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(['menunggu' => 'Menunggu', 'terverifikasi' => 'Terverifikasi', 'ditolak' => 'Ditolak'])
                    ->default('menunggu'),
            ])
            ->recordActions([
                Action::make('verifikasi')
                    ->label('Verifikasi')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Infaq $r) => $r->status !== 'terverifikasi')
                    ->requiresConfirmation()
                    ->modalHeading('Verifikasi infaq ini?')
                    ->modalDescription('Catatan akan ditandai terverifikasi DAN otomatis masuk ke Laporan Keuangan (Kas).')
                    ->action(function (Infaq $r) {
                        // Logika dipusatkan di model Infaq::verifikasi() supaya bisa diuji terpisah.
                        $kas = $r->verifikasi(auth()->id());
                        Notification::make()
                            ->title($kas ? 'Infaq terverifikasi & tercatat di Kas' : 'Infaq terverifikasi (sudah pernah tercatat)')
                            ->success()
                            ->send();
                    }),
                Action::make('tolak')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Infaq $r) => $r->status === 'menunggu')
                    ->requiresConfirmation()
                    ->action(function (Infaq $r) {
                        $r->status = 'ditolak';
                        $r->diverifikasi_oleh = auth()->id();
                        $r->save();
                        Notification::make()->title('Infaq ditolak')->warning()->send();
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
