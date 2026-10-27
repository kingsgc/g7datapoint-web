<?php

namespace App\Filament\Admin\Resources\UserAccountDetailsResource\Pages;

use App\Filament\Admin\Resources\UserAccountDetailsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUserAccountDetails extends ListRecords
{
    protected static string $resource = UserAccountDetailsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
