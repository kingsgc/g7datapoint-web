<?php

namespace App\Filament\Admin\Resources\BlockNumberResource\Pages;

use App\Filament\Admin\Resources\BlockNumberResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBlockNumber extends EditRecord
{
    protected static string $resource = BlockNumberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
