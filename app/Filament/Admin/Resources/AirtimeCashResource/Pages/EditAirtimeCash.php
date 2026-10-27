<?php

namespace App\Filament\Admin\Resources\AirtimeCashResource\Pages;

use App\Filament\Admin\Resources\AirtimeCashResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAirtimeCash extends EditRecord
{
    protected static string $resource = AirtimeCashResource::class;

    protected function getHeaderActions(): array
    {
        return [
          //  Actions\DeleteAction::make(),
        ];
    }
}
