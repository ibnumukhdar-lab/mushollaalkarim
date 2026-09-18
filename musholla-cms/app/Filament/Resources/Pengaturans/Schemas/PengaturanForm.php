<?php

namespace App\Filament\Resources\Pengaturans\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class PengaturanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('kunci')
                    ->required(),
                Textarea::make('nilai')
                    ->default(null)
                    ->columnSpanFull(),
            ]);
    }
}
