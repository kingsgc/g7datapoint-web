<?php

namespace App\Filament\Admin\Resources\StockBalanceResource\Pages;

use App\Filament\Admin\Resources\StockBalanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStockBalances extends ListRecords
{
    protected static string $resource = StockBalanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
