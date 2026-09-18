<?php

namespace App\Filament\Resources\Santris\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SantriForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nama')->label('Nama lengkap')
                    ->required(),
                Select::make('jenis_kelamin')
                    ->options(['L' => 'L', 'P' => 'P'])
                    ->default('L')
                    ->required(),
                TextInput::make('tempat_lahir')->label('Tempat lahir')
                    ->default(null),
                DatePicker::make('tanggal_lahir'),
                TextInput::make('kelompok')
                    ->default(null),
                TextInput::make('kelas_sekolah')->label('Kelas / sekolah')
                    ->default(null),
                TextInput::make('ortu_user_id')->label('Akun orang tua')->visible(false)
                    ->numeric()
                    ->default(null),
                TextInput::make('ustadz_id')->label('Ustadz pembina')
                    ->numeric()
                    ->default(null),
                Textarea::make('catatan')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('foto_path')
                    ->default(null),
                Toggle::make('aktif')
                    ->required(),
            ]);
    }
}
