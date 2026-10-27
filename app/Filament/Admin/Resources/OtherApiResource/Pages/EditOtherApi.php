<?php

namespace App\Filament\Admin\Resources\OtherApiResource\Pages;

use App\Filament\Admin\Resources\OtherApiResource;
use Filament\Resources\Pages\EditRecord;

class EditOtherApi extends EditRecord
{
    protected static string $resource = OtherApiResource::class;

    /**
     * No DeleteAction here on purpose: other_api is a singleton row that the
     * purchase controllers depend on, and OtherApiPolicy::delete() is false.
     */
    protected function getHeaderActions(): array
    {
        return [];
    }
}