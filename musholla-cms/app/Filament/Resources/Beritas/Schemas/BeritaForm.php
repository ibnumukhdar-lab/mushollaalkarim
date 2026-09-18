<?php

namespace App\Filament\Resources\Beritas\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class BeritaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('judul')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                Textarea::make('ringkasan')
                    ->default(null)
                    ->columnSpanFull(),
                Textarea::make('isi')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('kategori')
                    ->default(null),
                TextInput::make('gambar_path')
                    ->default(null),
                DateTimePicker::make('terbit_at'),
                TextInput::make('penulis_id')
                    ->numeric()
                    ->default(null),
            ]);
    }
}
