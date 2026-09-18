<?php

namespace App\Filament\Resources\Infaqs;

use App\Filament\Resources\Infaqs\Pages\CreateInfaq;
use App\Filament\Resources\Infaqs\Pages\EditInfaq;
use App\Filament\Resources\Infaqs\Pages\ListInfaqs;
use App\Filament\Resources\Infaqs\Schemas\InfaqForm;
use App\Filament\Resources\Infaqs\Tables\InfaqsTable;
use App\Models\Infaq;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class InfaqResource extends Resource
{
    protected static ?string $model = Infaq::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return InfaqForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InfaqsTable::configure($table);
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
            'index' => ListInfaqs::route('/'),
            'create' => CreateInfaq::route('/create'),
            'edit' => EditInfaq::route('/{record}/edit'),
        ];
    }

    /** Jumlah kiriman infaq yang belum diperiksa — tampil sebagai lencana di menu. */
    public static function getNavigationBadge(): ?string
    {
        $n = \App\Models\Infaq::query()->where('status', 'menunggu')->count();

        return $n > 0 ? (string) $n : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
