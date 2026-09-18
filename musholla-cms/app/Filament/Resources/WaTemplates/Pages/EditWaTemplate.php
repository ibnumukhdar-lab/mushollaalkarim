<?php

namespace App\Filament\Resources\WaTemplates\Pages;

use App\Filament\Resources\WaTemplates\WaTemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWaTemplate extends EditRecord
{
    protected static string $resource = WaTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
