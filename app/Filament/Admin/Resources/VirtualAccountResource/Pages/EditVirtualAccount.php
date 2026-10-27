<?php

namespace App\Filament\Admin\Resources\VirtualAccountResource\Pages;

use App\Filament\Admin\Resources\VirtualAccountResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditVirtualAccount extends EditRecord
{
    protected static string $resource = VirtualAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('delete')
                ->label('Delete Virtual Account')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function ($record) {
                    $record->palmpay = null;
                    $record->kolo_moni = null;
                    $record->save();
                }),
        ];
    }
}
