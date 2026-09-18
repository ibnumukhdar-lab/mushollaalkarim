<?php

namespace App\Filament\Resources\Kajians;

use App\Filament\Resources\Kajians\Pages\CreateKajian;
use App\Filament\Resources\Kajians\Pages\EditKajian;
use App\Filament\Resources\Kajians\Pages\ListKajians;
use App\Filament\Resources\Kajians\Schemas\KajianForm;
use App\Filament\Resources\Kajians\Tables\KajiansTable;
use App\Models\Kajian;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class KajianResource extends Resource
{
    protected static ?string $model = Kajian::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return KajianForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KajiansTable::configure($table);
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
            'index' => ListKajians::route('/'),
            'create' => CreateKajian::route('/create'),
            'edit' => EditKajian::route('/{record}/edit'),
        ];
    }
}
