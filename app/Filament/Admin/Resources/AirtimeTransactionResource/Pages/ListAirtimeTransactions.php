<?php

namespace App\Filament\Admin\Resources\AirtimeTransactionResource\Pages;

use App\Filament\Admin\Resources\AirtimeTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Pages\Concerns\ExposesTableToWidgets;

class ListAirtimeTransactions extends ListRecords
{
     use ExposesTableToWidgets; 
    protected static string $resource = AirtimeTransactionResource::class;
   
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
     protected function getHeaderWidgets(): array
    {
        return AirtimeTransactionResource::getWidgets();
    }
}
