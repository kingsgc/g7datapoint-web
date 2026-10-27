<?php

namespace App\Filament\Admin\Resources\CableTransactionResource\Pages;

use App\Filament\Admin\Resources\CableTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Pages\Concerns\ExposesTableToWidgets;
class ListCableTransactions extends ListRecords
{
     use ExposesTableToWidgets;
    protected static string $resource = CableTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return CableTransactionResource::getWidgets();
    }
}
