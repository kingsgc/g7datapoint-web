<?php

namespace App\Filament\Admin\Resources\StoreDataCardPlanResource\Pages;

use App\Filament\Admin\Resources\StoreDataCardPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStoreDataCardPlan extends EditRecord
{
    protected static string $resource = StoreDataCardPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
