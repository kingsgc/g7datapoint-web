<?php

namespace App\Filament\Admin\Resources\StockBalanceResource\Pages;

use App\Filament\Admin\Resources\StockBalanceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStockBalance extends EditRecord
{
    protected static string $resource = StockBalanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
        ];
    }
}
