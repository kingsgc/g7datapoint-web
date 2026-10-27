<?php

namespace App\Filament\Admin\Resources\RechargeCardPlanResource\Pages;

use App\Filament\Admin\Resources\RechargeCardPlanResource;
use App\Models\RechargeCardPlan;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateRechargeCardPlan extends CreateRecord
{
    protected static string $resource = RechargeCardPlanResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $lastPlan = RechargeCardPlan::orderByDesc('id')->first();
        $nextPlanId = (string) ($lastPlan ? ((int) $lastPlan->plan_id + 1) : 1);
        $data['plan_id'] = $nextPlanId;
        return $data;
    }
}
