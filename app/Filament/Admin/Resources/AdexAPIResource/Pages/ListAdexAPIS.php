<?php

namespace App\Filament\Admin\Resources\AdexAPIResource\Pages;

use App\Filament\Admin\Resources\AdexAPIResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAdexAPIS extends ListRecords
{
    protected static string $resource = AdexAPIResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
