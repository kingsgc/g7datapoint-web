<?php

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return UserResource::getWidgets();
    }
    public function getTabs(): array
    {
        return [
            null => Tab::make('All'),
            'active' => Tab::make()->query(fn($query) => $query->where('status', 1)),
            'unverified' => Tab::make()->query(fn($query) => $query->where('status', 0)),
            'Banned' => Tab::make()->query(fn($query) => $query->where('status', 2)),
            'Deactivated' => Tab::make()->query(fn($query) => $query->where('status', 3)),
        ];
    }
}
