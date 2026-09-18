<?php

namespace App\Filament\Resources\WaBroadcasts\Pages;

use App\Filament\Resources\WaBroadcasts\WaBroadcastResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWaBroadcast extends EditRecord
{
    protected static string $resource = WaBroadcastResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
