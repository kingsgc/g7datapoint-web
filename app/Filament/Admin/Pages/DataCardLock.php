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
class DataCardLock extends Page
{
     use HasPageShield;
    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';
    protected static string $view = 'filament.admin.pages.data-card-lock';
    protected static ?string $navigationGroup = 'Lock / Unlock';
    protected static ?string $title = 'Data Card Lock Settings';
    protected static ?int $navigationSort = 36;

    public array $data = [];

    public function mount(): void
    {
        $networks = Network::all();
        foreach ($networks as $network) {
            $this->data[strtolower($network->network)] = [
                'data_card' => (bool) $network->data_card,
            ];
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Data Card Lock Controls')
                    ->description('Enable or disable Data Card usage for each network.')
                    ->schema([
                        Grid::make(2)->schema([
                            Toggle::make('mtn.data_card')->label('MTN Data Card'),
                            Toggle::make('airtel.data_card')->label('Airtel Data Card'),
                            Toggle::make('glo.data_card')->label('GLO Data Card'),
                            Toggle::make('9mobile.data_card')->label('9mobile Data Card'),
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
                    'data_card' => $settings['data_card'] ?? false,
                ]
            );
        }

        Notification::make()
            ->title('Data card lock settings saved successfully!')
            ->success()
            ->send();
    }
}
