<?php

namespace App\Filament\Admin\Resources\BillResource\Pages;

use App\Filament\Admin\Resources\BillResource;
use App\Models\BillPlan;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateBill extends CreateRecord
{
    protected static string $resource = BillResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['added_by'] = auth()->user()->username;
        $lastPlan = BillPlan::orderByDesc('id')->first();
        $nextPlanId = (string) ($lastPlan ? ((int) $lastPlan->plan_id + 1) : 1);
        $data['plan_id'] = $nextPlanId;
        return $data;
    }
}
