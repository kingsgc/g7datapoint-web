<?php

namespace App\Filament\Admin\Resources\StoreRechargeCardResource\Pages;

use App\Filament\Admin\Resources\StoreRechargeCardResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStoreRechargeCard extends EditRecord
{
    protected static string $resource = StoreRechargeCardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
