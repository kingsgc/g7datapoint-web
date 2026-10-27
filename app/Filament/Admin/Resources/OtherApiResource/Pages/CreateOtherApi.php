<?php

namespace App\Filament\Admin\Resources\OtherApiResource\Pages;

use App\Filament\Admin\Resources\OtherApiResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOtherApi extends CreateRecord
{
    protected static string $resource = OtherApiResource::class;

    /**
     * This row is a singleton (everything reads OtherApi::first()). Guard
     * against a second one being created by navigating straight to the URL.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        abort_if(
            \App\Models\OtherApi::count() > 0,
            403,
            'other_api already holds its single credential row. Edit it instead of creating another.'
        );

        return $data;
    }
}