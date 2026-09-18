<?php

namespace App\Filament\Resources\Ustadzs;

use App\Filament\Resources\Ustadzs\Pages\CreateUstadz;
use App\Filament\Resources\Ustadzs\Pages\EditUstadz;
use App\Filament\Resources\Ustadzs\Pages\ListUstadzs;
use App\Filament\Resources\Ustadzs\Schemas\UstadzForm;
use App\Filament\Resources\Ustadzs\Tables\UstadzsTable;
use App\Models\Ustadz;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class UstadzResource extends Resource
{
    protected static ?string $model = Ustadz::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return UstadzForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UstadzsTable::configure($table);
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
            'index' => ListUstadzs::route('/'),
            'create' => CreateUstadz::route('/create'),
            'edit' => EditUstadz::route('/{record}/edit'),
        ];
    }
}
