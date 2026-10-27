<?php

namespace App\Filament\Admin\Resources\ManualFundingResource\Pages;

use App\Filament\Admin\Resources\ManualFundingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Pages\Concerns\ExposesTableToWidgets;
class ListManualFundings extends ListRecords
{
     use ExposesTableToWidgets;
    protected static string $resource = ManualFundingResource::class;

    protected function getHeaderActions(): array
    {
        return [
          //  Actions\CreateAction::make(),
        ];
    }

     protected function getHeaderWidgets(): array
    {
        return ManualFundingResource::getWidgets();
    }
}
