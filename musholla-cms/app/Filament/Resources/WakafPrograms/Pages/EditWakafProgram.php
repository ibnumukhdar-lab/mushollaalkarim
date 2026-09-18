<?php

namespace App\Filament\Resources\WakafPrograms\Pages;

use App\Filament\Resources\WakafPrograms\WakafProgramResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWakafProgram extends EditRecord
{
    protected static string $resource = WakafProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
