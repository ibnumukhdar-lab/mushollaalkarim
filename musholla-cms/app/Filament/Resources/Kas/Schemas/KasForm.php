<?php

namespace App\Filament\Resources\Kas\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class KasForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('tanggal')
                    ->label('Tanggal transaksi')
                    ->default(now())
                    ->required(),
                Select::make('jenis')
                    ->label('Jenis')
                    ->options(['masuk' => 'Uang masuk', 'keluar' => 'Uang keluar'])
                    ->default('masuk')
                    ->required()
                    ->native(false),
                TextInput::make('jumlah')
                    ->label('Jumlah')
                    ->prefix('Rp')
                    ->numeric()
                    ->minValue(0)
                    ->required()
                    ->default(0),
                TextInput::make('kategori')
                    ->label('Kategori')
                    ->datalist([
                        'Infaq & sedekah', 'Donasi', 'Zakat', 'Wakaf',
                        'Operasional', 'Listrik & air', 'Perbaikan', 'Kebersihan',
                        'Kegiatan & kajian', 'Pendidikan Al-Qur\'an', 'Konsumsi', 'Lain-lain',
                    ])
                    ->helperText('Boleh diisi bebas, atau pilih dari daftar yang muncul.'),
                Textarea::make('keterangan')
                    ->label('Keterangan')
                    ->rows(3)
                    ->columnSpanFull(),
                FileUpload::make('bukti_path')
                    ->label('Bukti (foto nota/struk)')
                    ->image()
                    ->directory('bukti-kas')
                    ->disk('public')
                    ->imageEditor()
                    ->columnSpanFull(),
                Hidden::make('dicatat_oleh')
                    ->default(fn () => auth()->id()),
            ]);
    }
}
