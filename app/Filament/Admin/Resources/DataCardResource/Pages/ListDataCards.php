<?php

namespace App\Filament\Admin\Resources\DataCardResource\Pages;

use App\Filament\Admin\Resources\DataCardResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Pages\Concerns\ExposesTableToWidgets;
class ListDataCards extends ListRecords
{
    protected static string $resource = DataCardResource::class;
       use ExposesTableToWidgets; 
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
     protected function getHeaderWidgets(): array{
        return DataCardResource::getWidgets();
    }
}
 