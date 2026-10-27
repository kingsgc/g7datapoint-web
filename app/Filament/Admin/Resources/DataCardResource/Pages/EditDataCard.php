<?php

namespace App\Filament\Admin\Resources\DataCardResource\Pages;

use App\Filament\Admin\Resources\DataCardResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDataCard extends EditRecord
{
    protected static string $resource = DataCardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
        ];
    }
}
