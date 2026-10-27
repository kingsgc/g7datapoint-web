<?php

namespace App\Filament\Admin\Resources\NetworkResource\Pages;

use App\Filament\Admin\Resources\NetworkResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditNetwork extends EditRecord
{
    protected static string $resource = NetworkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
