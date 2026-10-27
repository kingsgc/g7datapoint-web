<?php

namespace App\Filament\Admin\Resources\ManualFundingResource\Pages;

use App\Filament\Admin\Resources\ManualFundingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditManualFunding extends EditRecord
{
    protected static string $resource = ManualFundingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
