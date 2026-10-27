<?php

namespace App\Filament\Admin\Resources\CableResource\Pages;

use App\Filament\Admin\Resources\CableResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCable extends EditRecord
{
    protected static string $resource = CableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
