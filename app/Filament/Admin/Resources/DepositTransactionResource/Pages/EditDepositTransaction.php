<?php

namespace App\Filament\Admin\Resources\DepositTransactionResource\Pages;

use App\Filament\Admin\Resources\DepositTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDepositTransaction extends EditRecord
{
    protected static string $resource = DepositTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
