<?php

namespace App\Filament\Admin\Resources\ResultCheckerResource\Pages;

use App\Filament\Admin\Resources\ResultCheckerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditResultChecker extends EditRecord
{
    protected static string $resource = ResultCheckerResource::class;

    protected function getHeaderActions(): array
    {
        return [
          //  Actions\DeleteAction::make(),
        ];
    }
}
