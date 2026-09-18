<?php

namespace App\Filament\Resources\WakafPrograms\Pages;

use App\Filament\Resources\WakafPrograms\WakafProgramResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWakafPrograms extends ListRecords
{
    protected static string $resource = WakafProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
