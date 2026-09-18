<?php

namespace App\Filament\Resources\Kajians\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class KajianForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('judul')
                    ->required(),
                TextInput::make('pemateri')
                    ->default(null),
                TextInput::make('tema')
                    ->default(null),
                DatePicker::make('tanggal'),
                TextInput::make('waktu_mulai')
                    ->default(null),
                TextInput::make('waktu_selesai')
                    ->default(null),
                TextInput::make('tempat')
                    ->default(null),
                Textarea::make('keterangan')
                    ->default(null)
                    ->columnSpanFull(),
                Toggle::make('rutin_mingguan')
                    ->required(),
                Toggle::make('aktif')
                    ->required(),
            ]);
    }
}
