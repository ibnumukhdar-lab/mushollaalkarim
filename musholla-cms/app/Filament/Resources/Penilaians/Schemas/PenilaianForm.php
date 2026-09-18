<?php

namespace App\Filament\Resources\Penilaians\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class PenilaianForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('santri_id')
                    ->required()
                    ->numeric(),
                TextInput::make('ustadz_id')
                    ->numeric()
                    ->default(null),
                DatePicker::make('tanggal')
                    ->required(),
                Select::make('jenis')
                    ->options(['hafalan' => 'Hafalan', 'iqro' => 'Iqro', 'tilawah' => 'Tilawah', 'adab' => 'Adab'])
                    ->default('hafalan')
                    ->required(),
                TextInput::make('kategori')
                    ->default(null),
                Textarea::make('detail_materi')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('nilai_1')
                    ->numeric()
                    ->default(null),
                TextInput::make('nilai_2')
                    ->numeric()
                    ->default(null),
                TextInput::make('nilai_3')
                    ->numeric()
                    ->default(null),
                TextInput::make('ustadz_nama')
                    ->default(null),
                TextInput::make('juz')
                    ->default(null),
                TextInput::make('surah')
                    ->default(null),
                TextInput::make('ayat')
                    ->default(null),
                TextInput::make('iqro_jilid')
                    ->default(null),
                TextInput::make('iqro_halaman')
                    ->default(null),
                TextInput::make('nilai')
                    ->default(null),
                Textarea::make('deskripsi')
                    ->default(null)
                    ->columnSpanFull(),
                Textarea::make('catatan_ustadz')
                    ->default(null)
                    ->columnSpanFull(),
            ]);
    }
}
