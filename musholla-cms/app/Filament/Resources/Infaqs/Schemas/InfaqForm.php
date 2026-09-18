<?php

namespace App\Filament\Resources\Infaqs\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class InfaqForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nama_donatur')->label('Nama donatur')
                    ->required(),
                TextInput::make('no_wa')->label('Nomor WhatsApp')
                    ->default(null),
                TextInput::make('nominal')->label('Nominal')->prefix('Rp')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                DatePicker::make('tanggal')
                    ->required(),
                TextInput::make('tujuan')->label('Untuk')
                    ->default(null),
                TextInput::make('bukti_path')->label('Bukti')->visible(false)
                    ->default(null),
                Select::make('status')
                    ->options(['menunggu' => 'Menunggu', 'terverifikasi' => 'Terverifikasi', 'ditolak' => 'Ditolak'])
                    ->default('menunggu')
                    ->required(),
                Textarea::make('keterangan')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('diverifikasi_oleh')->label('Diverifikasi oleh')->visible(false)
                    ->numeric()
                    ->default(null),
            ]);
    }
}
