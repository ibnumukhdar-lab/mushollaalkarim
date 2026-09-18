<?php

namespace App\Filament\Resources\WaTemplates;

use App\Filament\Resources\WaTemplates\Pages\CreateWaTemplate;
use App\Filament\Resources\WaTemplates\Pages\EditWaTemplate;
use App\Filament\Resources\WaTemplates\Pages\ListWaTemplates;
use App\Filament\Resources\WaTemplates\Schemas\WaTemplateForm;
use App\Filament\Resources\WaTemplates\Tables\WaTemplatesTable;
use App\Models\WaTemplate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WaTemplateResource extends Resource
{
    protected static ?string $model = WaTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return WaTemplateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WaTemplatesTable::configure($table);
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
            'index' => ListWaTemplates::route('/'),
            'create' => CreateWaTemplate::route('/create'),
            'edit' => EditWaTemplate::route('/{record}/edit'),
        ];
    }
}
