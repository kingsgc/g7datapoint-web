<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Actions\Action;
use App\Models\Network;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
class DataLock extends Page
{
     use HasPageShield;
    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';
    protected static string $view = 'filament.admin.pages.data-lock';
    protected static ?string $navigationGroup = 'Lock / Unlock';
    protected static ?string $navigationLabel = 'Data Lock Settings';
    protected static ?string $title = 'Data Lock Settings';
    protected static ?int $navigationSort = 33;

    public array $data = [];

    public function mount(): void
    {
        $networks = Network::all();
        foreach ($networks as $network) {
            $this->data[strtolower($network->network)] = [
                'sme' => (bool)$network->network_sme,
                'cg' => (bool)$network->network_cg,
                'gifting' => (bool)$network->network_g,
            ];
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Data Lock Controls')
                    ->description('Enable or disable SME, CG, and Gifting data plans per network.')
                    ->schema([
                        Grid::make(2)->schema([
                            Toggle::make('mtn.sme')->label('MTN SME'),
                            Toggle::make('mtn.cg')->label('MTN CG'),
                            Toggle::make('mtn.gifting')->label('MTN Gifting'),

                            Toggle::make('airtel.sme')->label('AIRTEL SME'),
                            Toggle::make('airtel.cg')->label('AIRTEL CG'),
                            Toggle::make('airtel.gifting')->label('AIRTEL Gifting'),

                            Toggle::make('glo.sme')->label('GLO SME'),
                            Toggle::make('glo.cg')->label('GLO CG'),
                            Toggle::make('glo.gifting')->label('GLO Gifting'),

                            Toggle::make('9mobile.sme')->label('9MOBILE SME'),
                            Toggle::make('9mobile.cg')->label('9MOBILE CG'),
                            Toggle::make('9mobile.gifting')->label('9MOBILE Gifting'),
                        ])
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
                    'network_sme' => $settings['sme'] ?? false,
                    'network_cg' => $settings['cg'] ?? false,
                    'network_g' => $settings['gifting'] ?? false,
                ]
            );
        }
        \Filament\Notifications\Notification::make()
            ->title('Data lock settings saved successfully!')
            ->success()
            ->send();
    }
}
