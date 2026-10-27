<?php

namespace App\Filament\Admin\Resources\DataCardPlanResource\Pages;

use App\Filament\Admin\Resources\DataCardPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDataCardPlans extends ListRecords
{
    protected static string $resource = DataCardPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
