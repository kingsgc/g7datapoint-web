<?php

namespace App\Filament\Admin\Resources\BulkSMSResource\Pages;

use App\Filament\Admin\Resources\BulkSMSResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBulkSMS extends EditRecord
{
    protected static string $resource = BulkSMSResource::class;

    protected function getHeaderActions(): array
    {
        return [
          //  Actions\DeleteAction::make(),
        ];
    }
}
