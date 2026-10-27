<?php

namespace App\Filament\Admin\Resources\DataTransactionResource\Pages;

use App\Filament\Admin\Resources\DataTransactionResource;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDataTransactions extends ListRecords
{
    use ExposesTableToWidgets;
    protected static string $resource = DataTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
    protected function getHeaderWidgets(): array
    {
        return DataTransactionResource::getWidgets();
    }
}
