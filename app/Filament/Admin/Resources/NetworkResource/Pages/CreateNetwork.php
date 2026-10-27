<?php

namespace App\Filament\Admin\Resources\NetworkResource\Pages;

use App\Filament\Admin\Resources\NetworkResource;
use App\Models\Network;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateNetwork extends CreateRecord
{
    protected static string $resource = NetworkResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $lastPlan = Network::orderByDesc('id')->first();
        $nextPlanId = (string) ($lastPlan ? ((int) $lastPlan->plan_id + 1) : 1);
        $data['plan_id'] = $nextPlanId;
        return $data;
    }
}
