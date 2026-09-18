<?php

namespace App\Filament\Resources\Infaqs\Pages;

use App\Filament\Resources\Infaqs\InfaqResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInfaqs extends ListRecords
{
    protected static string $resource = InfaqResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
