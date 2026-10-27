<?php

namespace App\Filament\Admin\Resources\DepositTransactionResource\Pages;

use App\Filament\Admin\Resources\DepositTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Pages\Concerns\ExposesTableToWidgets;

class ListDepositTransactions extends ListRecords
{
      use ExposesTableToWidgets; 
    protected static string $resource = DepositTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
    protected function getHeaderWidgets(): array
    {
        return DepositTransactionResource::getWidgets();
    }
}
