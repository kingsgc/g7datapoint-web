<?php

namespace App\Filament\Admin\Resources\BlockNumberResource\Pages;

use App\Filament\Admin\Resources\BlockNumberResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateBlockNumber extends CreateRecord
{
    protected static string $resource = BlockNumberResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['added_by'] = auth()->user()->username;
        return $data;
    }
}
