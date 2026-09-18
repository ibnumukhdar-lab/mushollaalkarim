<?php

namespace App\Filament\Resources\Ustadzs\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UstadzForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nama')
                    ->required(),
                TextInput::make('user_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('bidang')
                    ->default(null),
                TextInput::make('no_wa')
                    ->default(null),
                Textarea::make('catatan')
                    ->default(null)
                    ->columnSpanFull(),
                Toggle::make('aktif')
                    ->required(),
            ]);
    }
}
