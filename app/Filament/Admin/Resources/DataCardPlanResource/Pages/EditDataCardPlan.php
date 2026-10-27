<?php

namespace App\Filament\Admin\Resources\DataCardPlanResource\Pages;

use App\Filament\Admin\Resources\DataCardPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDataCardPlan extends EditRecord
{
    protected static string $resource = DataCardPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
