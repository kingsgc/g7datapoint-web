<?php

namespace App\Filament\Admin\Resources\AirtimeTransactionResource\Pages;

use App\Filament\Admin\Resources\AirtimeTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAirtimeTransaction extends EditRecord
{
    protected static string $resource = AirtimeTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //   Actions\DeleteAction::make(),
        ];
    }
}
