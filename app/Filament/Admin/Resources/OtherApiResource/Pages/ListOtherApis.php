<?php

namespace App\Filament\Admin\Resources\OtherApiResource\Pages;

use App\Filament\Admin\Resources\OtherApiResource;
use App\Models\OtherApi;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOtherApis extends ListRecords
{
    protected static string $resource = OtherApiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Only offered on a fresh database where the singleton row is
            // missing; otherwise a second row would never be read by
            // OtherApi::first() and would sit there as dead, confusing data.
            Actions\CreateAction::make()
                ->label('Create credential row')
                ->visible(fn (): bool => OtherApi::count() === 0),
        ];
    }
}