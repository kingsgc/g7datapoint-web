<?php

namespace App\Filament\Admin\Resources\DataTransactionResource\Pages;

use App\Filament\Admin\Resources\DataTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDataTransaction extends EditRecord
{
    protected static string $resource = DataTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
        ];
    }
}
