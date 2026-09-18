<?php

namespace App\Filament\Resources\Kajians\Pages;

use App\Filament\Resources\Kajians\KajianResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListKajians extends ListRecords
{
    protected static string $resource = KajianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
