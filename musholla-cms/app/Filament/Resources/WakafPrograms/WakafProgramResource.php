<?php

namespace App\Filament\Resources\WakafPrograms;

use App\Filament\Resources\WakafPrograms\Pages\CreateWakafProgram;
use App\Filament\Resources\WakafPrograms\Pages\EditWakafProgram;
use App\Filament\Resources\WakafPrograms\Pages\ListWakafPrograms;
use App\Filament\Resources\WakafPrograms\Schemas\WakafProgramForm;
use App\Filament\Resources\WakafPrograms\Tables\WakafProgramsTable;
use App\Models\WakafProgram;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WakafProgramResource extends Resource
{
    protected static ?string $model = WakafProgram::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return WakafProgramForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WakafProgramsTable::configure($table);
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
            'index' => ListWakafPrograms::route('/'),
            'create' => CreateWakafProgram::route('/create'),
            'edit' => EditWakafProgram::route('/{record}/edit'),
        ];
    }
}
