<?php

namespace App\Filament\Admin\Resources\TransactionHistoryResource\Pages;

use App\Filament\Admin\Resources\TransactionHistoryResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTransactionHistory extends CreateRecord
{
    protected static string $resource = TransactionHistoryResource::class;
}
