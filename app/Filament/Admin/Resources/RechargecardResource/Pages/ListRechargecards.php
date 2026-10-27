<?php

namespace App\Filament\Admin\Resources\RechargecardResource\Pages;

use App\Filament\Admin\Resources\RechargecardResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Pages\Concerns\ExposesTableToWidgets;
class ListRechargecards extends ListRecords
{
    protected static string $resource = RechargecardResource::class;
     use ExposesTableToWidgets;
    protected function getHeaderActions(): array
    { 
        return [
            Actions\CreateAction::make(),
        ];
    }
    protected function getHeaderWidgets(): array{
        return RechargecardResource::getWidgets();
    }
}
