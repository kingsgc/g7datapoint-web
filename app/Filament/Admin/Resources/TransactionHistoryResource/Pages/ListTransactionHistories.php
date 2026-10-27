<?php

namespace App\Filament\Admin\Resources\TransactionHistoryResource\Pages;

use App\Filament\Admin\Resources\TransactionHistoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Pages\Concerns\ExposesTableToWidgets;

class ListTransactionHistories extends ListRecords
{
    use ExposesTableToWidgets;
    protected static string $resource = TransactionHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
    protected function getHeaderWidgets(): array
    {
        return TransactionHistoryResource::getWidgets();
    }
}
