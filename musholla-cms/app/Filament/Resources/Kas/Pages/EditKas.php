<?php

namespace App\Filament\Resources\Kas\Pages;

use App\Filament\Resources\Kas\KasResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditKas extends EditRecord
{
    protected static string $resource = KasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
