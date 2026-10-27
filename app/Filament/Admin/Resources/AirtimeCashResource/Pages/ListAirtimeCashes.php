<?php

namespace App\Filament\Admin\Resources\AirtimeCashResource\Pages;

use App\Filament\Admin\Resources\AirtimeCashResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Pages\Concerns\ExposesTableToWidgets;

class ListAirtimeCashes extends ListRecords
{
    protected static string $resource = AirtimeCashResource::class;
    use ExposesTableToWidgets; 
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
     protected function getHeaderWidgets(): array
    {
        return AirtimeCashResource::getWidgets();
    }
}
