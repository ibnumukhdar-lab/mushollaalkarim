<?php

namespace App\Filament\Resources\Santris;

use App\Filament\Resources\Santris\Pages\CreateSantri;
use App\Filament\Resources\Santris\Pages\EditSantri;
use App\Filament\Resources\Santris\Pages\ListSantris;
use App\Filament\Resources\Santris\Schemas\SantriForm;
use App\Filament\Resources\Santris\Tables\SantrisTable;
use App\Models\Santri;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SantriResource extends Resource
{
    protected static ?string $model = Santri::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return SantriForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SantrisTable::configure($table);
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
            'index' => ListSantris::route('/'),
            'create' => CreateSantri::route('/create'),
            'edit' => EditSantri::route('/{record}/edit'),
        ];
    }
}
