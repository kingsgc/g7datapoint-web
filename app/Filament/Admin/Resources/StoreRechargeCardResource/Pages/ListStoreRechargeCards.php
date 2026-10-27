<?php

namespace App\Filament\Admin\Resources\StoreRechargeCardResource\Pages;

use App\Filament\Admin\Resources\StoreRechargeCardResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStoreRechargeCards extends ListRecords
{
    protected static string $resource = StoreRechargeCardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
