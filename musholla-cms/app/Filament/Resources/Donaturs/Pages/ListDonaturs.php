<?php

namespace App\Filament\Resources\Donaturs\Pages;

use App\Filament\Resources\Donaturs\DonaturResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDonaturs extends ListRecords
{
    protected static string $resource = DonaturResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
