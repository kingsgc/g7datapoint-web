<?php

namespace App\Filament\Admin\Resources\BillTransactionResource\Pages;

use App\Filament\Admin\Resources\BillTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBillTransaction extends EditRecord
{
    protected static string $resource = BillTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
        ];
    }
}
