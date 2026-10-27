<?php

namespace App\Filament\Admin\Resources\BillResource\Pages;

use App\Filament\Admin\Resources\BillResource;
use App\Models\BillPlan;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Collection;

class ListBills extends ListRecords
{
    protected static string $resource = BillResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('adjust_commission')
                ->label('Adjust Commission (%)')
                ->form([
                    Forms\Components\Select::make('operation')
                        ->label('Operation')
                        ->options([
                            'increase' => 'Increase',
                            'decrease' => 'Decrease',
                        ])
                        ->required(),
                    Forms\Components\TextInput::make('percentage')
                        ->label('Percentage (%)')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->required(),
                ])
                ->action(function (array $data,) {
                    $percentage = $data['percentage'];
                    $operation = $data['operation'];
                    BillPlan::chunk(100, function ($records) use ($percentage, $operation) {
                        foreach ($records as $record) {
                            $current = $record->commission;
                            $newCommission = $operation === 'increase'
                                ? $current + $percentage
                                : $current - $percentage;
                            $newCommission = max(0, min($newCommission, 100));
                            $record->update(['commission' => $newCommission]);
                        }
                    });
                    Notification::make()->title('Success')->body("Commission {$operation}d by {$percentage}% for selected plans.")->success()->send();
                })
                ->requiresConfirmation()
                ->color('primary')
                ->icon('heroicon-o-banknotes'),
        ];
    }
}
