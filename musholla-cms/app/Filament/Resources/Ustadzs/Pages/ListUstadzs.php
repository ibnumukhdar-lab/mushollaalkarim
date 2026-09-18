<?php

namespace App\Filament\Resources\Ustadzs\Pages;

use App\Filament\Resources\Ustadzs\UstadzResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUstadzs extends ListRecords
{
    protected static string $resource = UstadzResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
