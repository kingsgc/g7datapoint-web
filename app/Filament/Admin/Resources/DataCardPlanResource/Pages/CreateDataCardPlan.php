<?php

namespace App\Filament\Admin\Resources\DataCardPlanResource\Pages;

use App\Filament\Admin\Resources\DataCardPlanResource;
use App\Models\DataCardPlan;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateDataCardPlan extends CreateRecord
{
    protected static string $resource = DataCardPlanResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $lastPlan = DataCardPlan::orderByDesc('id')->first();
        $nextPlanId = (string) ($lastPlan ? ((int) $lastPlan->plan_id + 1) : 1);
        $data['plan_id'] = $nextPlanId;
        return $data;
    }
}
