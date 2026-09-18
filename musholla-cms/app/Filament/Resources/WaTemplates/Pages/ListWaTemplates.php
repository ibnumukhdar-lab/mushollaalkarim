<?php

namespace App\Filament\Resources\WaTemplates\Pages;

use App\Filament\Resources\WaTemplates\WaTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWaTemplates extends ListRecords
{
    protected static string $resource = WaTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
