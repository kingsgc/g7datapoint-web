<?php

namespace App\Filament\Admin\Resources\BlockNumberResource\Pages;

use App\Filament\Admin\Resources\BlockNumberResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBlockNumbers extends ListRecords
{
    protected static string $resource = BlockNumberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
