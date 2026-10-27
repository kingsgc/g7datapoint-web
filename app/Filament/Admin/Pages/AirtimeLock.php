<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Actions\Action;
use App\Models\AirtimeLock as AirtimeLockModel;
use App\Models\Network;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
class AirtimeLock extends Page
{
     use HasPageShield;
    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';
    protected static string $view = 'filament.admin.pages.airtime-lock';
    protected static ?string $navigationGroup = 'Lock / Unlock';
    protected static ?string $title = 'Airtime Lock Settings';
    protected static ?int $navigationSort = 32;

    public array $data = [];

    public function mount(): void
    {
        $networks = Network::all();
        foreach ($networks as $network) {
            $this->data[strtolower($network->network)] = [
                'vtu' => (bool)$network->network_vtu,
                'share' => (bool)$network->network_share,
                'cash' => (bool)$network->cash
            ];
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Airtime Lock Controls')
                    ->description('Enable or disable VTU and SHARE & SELL for each network.')
                    ->schema([
                        Grid::make(2)->schema([
                            Toggle::make('mtn.vtu')->label('MTN VTU'),
                            Toggle::make('mtn.cash')->label('MTN (2Cash)'),
                            Toggle::make('mtn.share')->label('MTN SHARE & SELL'),

                            Toggle::make('airtel.vtu')->label('AIRTEL VTU'),
                            Toggle::make('airtel.cash')->label('AIRTEL (2Cash)'),
                            Toggle::make('airtel.share')->label('AIRTEL SHARE & SELL'),

                            Toggle::make('glo.vtu')->label('GLO VTU'),
                            Toggle::make('glo.cash')->label('GLO (2Cash)'),
                            Toggle::make('glo.share')->label('GLO SHARE & SELL'),

                            Toggle::make('9mobile.vtu')->label('9MOBILE VTU'),
                            Toggle::make('9mobile.cash')->label('9MOBILE (2Cash)'),
                            Toggle::make('9mobile.share')->label('9MOBILE SHARE & SELL'),
                        ]),
                    ])
            ])
            ->statePath('data');
    }
    protected function getFormActions(): Action
    {

        return Action::make('save')
            ->label('Save Settings')
            ->color('primary')
            ->action('save');
    }

    public function save(): void
    {
        foreach ($this->data as $network => $settings) {
            Network::updateOrCreate(
                ['network' => $network],
                [
                    'network_vtu' => $settings['vtu'] ?? false,
                    'network_share' => $settings['share'] ?? false,
                    'cash' => $settings['cash'] ?? false
                ]
            );
        }

        \Filament\Notifications\Notification::make()
            ->title('Airtime lock settings saved successfully!')
            ->success()
            ->send();
    }
}
