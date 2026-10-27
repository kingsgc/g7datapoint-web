<?php

namespace App\Filament\Admin\Resources\CableTransactionResource\Pages;

use App\Filament\Admin\Resources\CableTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCableTransaction extends EditRecord
{
    protected static string $resource = CableTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
        ];
    }
}
