<?php

namespace App\Filament\Admin\Resources\CableResource\Pages;

use App\Filament\Admin\Resources\CableResource;
use Filament\Actions;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ListRecords;

class ListCables extends ListRecords
{
    protected static string $resource = CableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('increaseAllPrices')
                ->label('Increase All Prices')
                ->icon('heroicon-m-banknotes')
                ->form([
                    TextInput::make('amount')
                        ->label('Amount to Add (₦)')
                        ->numeric()
                        ->required()->minValue(1)->prefix('₦')->suffix('NGN')->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2),
                ])
                ->requiresConfirmation()
                ->action(function (array $data) {
                    \App\Models\CablePlan::query()->each(function ($plan) use ($data) {
                        $current = floatval($plan->plan_price);
                        $increment = floatval($data['amount']);
                        $plan->update([
                            'plan_price' => $current + $increment,
                        ]);
                    });
                })
                ->successNotificationTitle('All prices updated successfully.'),
        ];
    }
}
