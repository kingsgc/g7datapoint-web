<?php

namespace App\Filament\Admin\Resources\StoreDataCardPlanResource\Pages;

use App\Filament\Admin\Resources\StoreDataCardPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStoreDataCardPlans extends ListRecords
{
    protected static string $resource = StoreDataCardPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
