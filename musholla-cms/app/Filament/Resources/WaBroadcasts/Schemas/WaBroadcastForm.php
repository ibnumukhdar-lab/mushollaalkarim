<?php

namespace App\Filament\Resources\WaBroadcasts\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class WaBroadcastForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('wa_template_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('pesan_terkirim')
                    ->default(null),
                TextInput::make('jumlah_target')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('terkirim')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('gagal')
                    ->required()
                    ->numeric()
                    ->default(0),
                DateTimePicker::make('mulai_at'),
                DateTimePicker::make('selesai_at'),
                TextInput::make('status')
                    ->required()
                    ->default('menunggu'),
                TextInput::make('oleh_user_id')
                    ->numeric()
                    ->default(null),
            ]);
    }
}
