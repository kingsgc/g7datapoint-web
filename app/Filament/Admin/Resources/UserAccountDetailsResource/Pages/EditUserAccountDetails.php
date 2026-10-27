<?php

namespace App\Filament\Admin\Resources\UserAccountDetailsResource\Pages;

use App\Filament\Admin\Resources\UserAccountDetailsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUserAccountDetails extends EditRecord
{
    protected static string $resource = UserAccountDetailsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
