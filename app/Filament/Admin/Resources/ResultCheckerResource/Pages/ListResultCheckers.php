<?php

namespace App\Filament\Admin\Resources\ResultCheckerResource\Pages;

use App\Filament\Admin\Resources\ResultCheckerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords; 
use Filament\Pages\Concerns\ExposesTableToWidgets;
class ListResultCheckers extends ListRecords
{
     use ExposesTableToWidgets; 
    protected static string $resource = ResultCheckerResource::class;
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
     protected function getHeaderWidgets(): array{
        return ResultCheckerResource::getWidgets();
    }
}
