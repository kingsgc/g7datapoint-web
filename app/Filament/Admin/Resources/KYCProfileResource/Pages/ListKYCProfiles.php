<?php

namespace App\Filament\Admin\Resources\KYCProfileResource\Pages;

use App\Filament\Admin\Resources\KYCProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListKYCProfiles extends ListRecords
{
    protected static string $resource = KYCProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
