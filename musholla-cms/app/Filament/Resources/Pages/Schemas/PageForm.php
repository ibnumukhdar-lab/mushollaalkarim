<?php

namespace App\Filament\Resources\Pages\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('judul')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                Textarea::make('isi')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('ringkasan')
                    ->default(null),
                Toggle::make('tampil_di_menu')
                    ->required(),
                TextInput::make('urutan_menu')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('meta_judul')
                    ->default(null),
                TextInput::make('meta_deskripsi')
                    ->default(null),
                DateTimePicker::make('terbit_at'),
            ]);
    }
}
