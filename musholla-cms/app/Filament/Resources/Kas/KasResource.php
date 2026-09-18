<?php

namespace App\Filament\Resources\Kas;

use App\Filament\Resources\Kas\Pages\CreateKas;
use App\Filament\Resources\Kas\Pages\EditKas;
use App\Filament\Resources\Kas\Pages\ListKas;
use App\Filament\Resources\Kas\Schemas\KasForm;
use App\Filament\Resources\Kas\Tables\KasTable;
use App\Models\Kas;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class KasResource extends Resource
{
    protected static ?string $model = Kas::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return KasForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KasTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKas::route('/'),
            'create' => CreateKas::route('/create'),
            'edit' => EditKas::route('/{record}/edit'),
        ];
    }
}
