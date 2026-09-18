<?php

namespace App\Filament\Resources\WaBroadcasts\Pages;

use App\Filament\Resources\WaBroadcasts\WaBroadcastResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWaBroadcasts extends ListRecords
{
    protected static string $resource = WaBroadcastResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
