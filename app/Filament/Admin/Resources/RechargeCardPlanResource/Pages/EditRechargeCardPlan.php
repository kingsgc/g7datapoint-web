<?php

namespace App\Filament\Admin\Resources\RechargeCardPlanResource\Pages;

use App\Filament\Admin\Resources\RechargeCardPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRechargeCardPlan extends EditRecord
{
    protected static string $resource = RechargeCardPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
