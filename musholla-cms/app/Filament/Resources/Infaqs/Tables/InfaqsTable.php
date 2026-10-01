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
                TextColumn::make('kas_id')
                    ->label('Kas')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'kas #'.$state : 'belum tercatat')
                    ->color(fn ($state) => $state ? 'success' : 'gray'),
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
                Action::make('catatKeKas')
                    ->label('Catat ke kas')
                    ->icon('heroicon-o-banknotes')
                    ->color('warning')
                    ->visible(fn (Infaq $r) => $r->status === 'terverifikasi' && blank($r->kas_id))
                    ->requiresConfirmation()
                    ->modalHeading('Catat infaq ini ke kas sekarang?')
                    ->modalDescription('Catatan terverifikasi yang belum punya baris kas — aman ditekan, idempoten.')
                    ->action(function (Infaq $r) {
                        $kas = $r->verifikasi(auth()->id());
                        Notification::make()
                            ->title($kas ? 'Tercatat ke kas (kas #'.$kas->id.')' : 'Sudah tercatat sebelumnya')
                            ->success()
                            ->send();
                    }),
                Action::make('tolak')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Infaq $r) => $r->status !== 'ditolak')
                    ->requiresConfirmation()
                    ->modalHeading('Tolak infaq ini?')
                    ->modalDescription('Bila infaq ini sudah tercatat di kas, baris kasnya ikut dihapus.')
                    ->action(function (Infaq $r) {
                        // Satukan perilaku dengan tombol panel: Infaq::tolak().
                        $hasil = $r->tolak(auth()->id());
                        Notification::make()
                            ->title($hasil['pesan'])
                            ->{$hasil['berhasil'] ? 'success' : 'danger'}()
                            ->send();
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
