<?php

namespace App\Filament\Admin\Resources\DataResource\Pages;

use App\Filament\Admin\Resources\DataResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditData extends EditRecord
{
    protected static string $resource = DataResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
