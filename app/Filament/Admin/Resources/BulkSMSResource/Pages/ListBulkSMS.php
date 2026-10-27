<?php

namespace App\Filament\Admin\Resources\BulkSMSResource\Pages;

use App\Filament\Admin\Resources\BulkSMSResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Pages\Concerns\ExposesTableToWidgets;
class ListBulkSMS extends ListRecords
{
    use ExposesTableToWidgets; 
    protected static string $resource = BulkSMSResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
    protected function getHeaderWidgets(): array
    {
        return BulkSMSResource::getWidgets();
    }
}
