<?php

namespace App\Filament\Admin\Resources\AppDownloadResource\Pages;

use App\Filament\Admin\Resources\AppDownloadResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAppDownloads extends ListRecords
{
    protected static string $resource = AppDownloadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
