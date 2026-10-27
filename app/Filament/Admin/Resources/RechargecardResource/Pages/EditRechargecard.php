<?php

namespace App\Filament\Admin\Resources\RechargecardResource\Pages;

use App\Filament\Admin\Resources\RechargecardResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRechargecard extends EditRecord
{
    protected static string $resource = RechargecardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
