<?php

namespace App\Filament\Admin\Pages;

use App\Models\Network;
use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Toggle;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
class RechargeCardLock extends Page
{
    use HasPageShield;
    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';
    protected static string $view = 'filament.admin.pages.recharge-card-lock';
    protected static ?string $navigationGroup = 'Lock / Unlock';
    protected static ?string $title = 'Recharge Card Lock Settings';
    protected static ?int $navigationSort = 37;

    public array $data = [];

    public function mount(): void
    {
        $networks = Network::all();
        foreach ($networks as $network) {
            $this->data[strtolower($network->network)] = [
                'recharge_card' => (bool) $network->recharge_card,
            ];
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Recharge Card Lock Controls')
                    ->description('Enable or disable Recharge Card usage for each network.')
                    ->schema([
                        Grid::make(2)->schema([
                            Toggle::make('mtn.recharge_card')->label('MTN Recharge Card'),
                            Toggle::make('airtel.recharge_card')->label('Airtel Recharge Card'),
                            Toggle::make('glo.recharge_card')->label('GLO Recharge Card'),
                            Toggle::make('9mobile.recharge_card')->label('9mobile Recharge Card'),
                        ]),
                    ]),
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
                    'recharge_card' => $settings['recharge_card'] ?? false,
                ]
            );
        }

        Notification::make()
            ->title('Recharge card lock settings saved successfully!')
            ->success()
            ->send();
    }
}
