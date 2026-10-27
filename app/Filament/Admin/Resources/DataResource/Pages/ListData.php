<?php

namespace App\Filament\Admin\Resources\DataResource\Pages;

use App\Filament\Admin\Resources\DataResource;
use App\Models\DataPlan;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ListRecords;

class ListData extends ListRecords
{
    protected static string $resource = DataResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('add_to_all_prices')
                ->label('Add to All Prices')
                ->form([
                    TextInput::make('amount')
                        ->label('Amount to Add (₦)')
                        ->numeric()
                        ->required()->minValue(1)->prefix('₦')->suffix('NGN')->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2),
                ])
                ->action(function (array $data) {
                    $amount = floatval($data['amount']);
                    $priceFields = ['smart', 'awuf', 'agent', 'api', 'special'];
                    DataPlan::chunk(100, function ($records) use ($priceFields, $amount) {
                        foreach ($records as $record) {
                            foreach ($priceFields as $field) {
                                if (is_numeric($record->{$field})) {
                                    $record->{$field} += $amount;
                                }
                            }
                            $record->save();
                        }
                    });
                    Notification::make()->title('Success')->body('Prices updated for all plans.')->success()->send();
                })
                ->icon('heroicon-o-currency-dollar')
                ->color('success')
                ->requiresConfirmation(),
        ];
    }
}
