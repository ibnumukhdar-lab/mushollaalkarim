<?php

namespace App\Filament\Resources\WaBroadcasts;

use App\Filament\Resources\WaBroadcasts\Pages\CreateWaBroadcast;
use App\Filament\Resources\WaBroadcasts\Pages\EditWaBroadcast;
use App\Filament\Resources\WaBroadcasts\Pages\ListWaBroadcasts;
use App\Filament\Resources\WaBroadcasts\Schemas\WaBroadcastForm;
use App\Filament\Resources\WaBroadcasts\Tables\WaBroadcastsTable;
use App\Models\WaBroadcast;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WaBroadcastResource extends Resource
{
    protected static ?string $model = WaBroadcast::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return WaBroadcastForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WaBroadcastsTable::configure($table);
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
            'index' => ListWaBroadcasts::route('/'),
            'create' => CreateWaBroadcast::route('/create'),
            'edit' => EditWaBroadcast::route('/{record}/edit'),
        ];
    }
}
