<?php

namespace App\Filament\Resources\WakafPrograms\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class WakafProgramForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nama')
                    ->required(),
                TextInput::make('slug')
                    ->label('Alamat halaman (slug)')
                    ->maxLength(120)
                    ->unique(ignoreRecord: true)
                    ->default(null)
                    ->helperText('Alamat /wakaf/<slug> saat dibagikan. Kosongkan agar dibuat otomatis dari nama program.'),
                Textarea::make('keterangan')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('target')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('terkumpul')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('gambar_path')
                    ->default(null),
                TextInput::make('urutan')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('aktif')
                    ->required(),
            ]);
    }
}
