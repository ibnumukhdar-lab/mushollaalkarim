<?php

namespace App\Filament\Resources\Donaturs\Pages;

use App\Filament\Resources\Donaturs\DonaturResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDonatur extends EditRecord
{
    protected static string $resource = DonaturResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
