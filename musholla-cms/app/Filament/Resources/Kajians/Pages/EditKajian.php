<?php

namespace App\Filament\Resources\Kajians\Pages;

use App\Filament\Resources\Kajians\KajianResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditKajian extends EditRecord
{
    protected static string $resource = KajianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
