<?php

namespace App\Filament\Admin\Resources\CableResource\Pages;

use App\Filament\Admin\Resources\CableResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateCable extends CreateRecord
{
    protected static string $resource = CableResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['added_by'] = auth()->user()->username;
        $lastPlanId = DB::table('cable_plan')
            ->selectRaw('MAX(CAST(plan_id AS UNSIGNED)) as max_plan_id')
            ->value('max_plan_id');
        $data['plan_id'] = (string) ((int) $lastPlanId + 1);
        return $data;
    }
}
