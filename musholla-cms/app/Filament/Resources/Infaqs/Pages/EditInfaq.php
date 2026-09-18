<?php

namespace App\Filament\Resources\Infaqs\Pages;

use App\Filament\Resources\Infaqs\InfaqResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInfaq extends EditRecord
{
    protected static string $resource = InfaqResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
