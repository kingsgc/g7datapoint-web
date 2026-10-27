<?php

namespace App\Filament\Admin\Resources\AdexAPIResource\Pages;

use App\Filament\Admin\Resources\AdexAPIResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Http;

class EditAdexAPI extends EditRecord
{
    protected static string $resource = AdexAPIResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Action::make('testConnection')
                ->label('Test Connection')
                ->icon('heroicon-o-wifi')
                ->color('secondary')
                ->action(function () {
                    $record = $this->record;

                    try {
                        $response = Http::withBasicAuth($record->username, $record->password)
                            ->post($record->website_url . '/api/user');
                        if ($response->ok()) {
                            $balance = $response->json('balance') ?? 'N/A';
                            $record->update(['balance' => $balance]);
                            Notification::make()
                                ->title('Connection successful')
                                ->body("Balance: ₦{$balance}")
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Connection failed')
                                ->body('Could not connect to the API. Please check your credentials or URL.')
                                ->danger()
                                ->send();
                        }
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Connection error')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
