<?php

namespace App\Filament\Admin\Resources\TransactionHistoryResource\Pages;

use App\Filament\Admin\Resources\TransactionHistoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTransactionHistory extends EditRecord
{
    protected static string $resource = TransactionHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //  Actions\DeleteAction::make(),
        ];
    }
}
