<?php

namespace App\Filament\Admin\Resources\StorePinResource\Pages;

use App\Filament\Admin\Resources\StorePinResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStorePin extends EditRecord
{
    protected static string $resource = StorePinResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
