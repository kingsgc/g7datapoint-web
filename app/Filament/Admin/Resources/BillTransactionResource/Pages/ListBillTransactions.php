<?php

namespace App\Filament\Admin\Resources\BillTransactionResource\Pages;

use App\Filament\Admin\Resources\BillTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Pages\Concerns\ExposesTableToWidgets;
class ListBillTransactions extends ListRecords
{
     use ExposesTableToWidgets; 
    protected static string $resource = BillTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    } 

    protected function getHeaderWidgets(): array
    {
        return BillTransactionResource::getWidgets();
    }
}
