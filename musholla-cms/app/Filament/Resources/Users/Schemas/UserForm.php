<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama tampilan')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Surel')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Select::make('peran')
                    ->label('Peran')
                    ->options([
                        'subscriber' => 'Subscriber — anggota biasa (hasil pendaftaran publik)',
                        'admin' => 'Admin — boleh masuk & mengelola panel',
                    ])
                    ->default('subscriber')
                    ->required()
                    ->native(false),
                TextInput::make('password')
                    ->label('Sandi')
                    ->password()
                    ->revealable()
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $operation) => $operation === 'create')
                    ->helperText('Kosongkan bila tidak ingin mengganti sandi. Minimal 8 huruf.'),
                TextInput::make('nama_lengkap')
                    ->label('Nama lengkap')
                    ->maxLength(255),
                TextInput::make('no_wa')
                    ->label('Nomor WhatsApp')
                    ->tel()
                    ->maxLength(20),
                Toggle::make('aktif')
                    ->label('Aktif — boleh masuk panel')
                    ->default(true),
            ]);
    }
}
