<?php

namespace App\Filament\Admin\Resources\StorePinResource\Pages;

use App\Filament\Admin\Resources\StorePinResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStorePins extends ListRecords
{
    protected static string $resource = StorePinResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
