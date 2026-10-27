<?php

namespace App\Filament\Admin\Resources\DataResource\Pages;

use App\Filament\Admin\Resources\DataResource;
use App\Models\DataPlan;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateData extends CreateRecord
{
    protected static string $resource = DataResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['added_by'] = auth()->user()->username;
        $lastPlanId = DB::table('data_plan')
            ->selectRaw('MAX(CAST(plan_id AS UNSIGNED)) as max_plan_id')
            ->value('max_plan_id');
        $data['plan_id'] = (string) ((int) $lastPlanId + 1);
        return $data;
    }
}
