<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use App\Models\AirtimeSelection as AirtimeSelectionModel;
use App\Models\AdexAPI;
use Filament\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
class AirtimeSelection extends Page
{
     use HasPageShield;
    protected static ?string $navigationIcon = 'heroicon-o-phone-arrow-up-right';
    protected static ?string $navigationGroup = "Vending";
    protected static ?string $navigationLabel = 'Airtime';
    protected static ?int $navigationSort = 52;
    protected static ?string $modelLabel = 'Airtime Vending';
    protected static string $view = 'filament.admin.pages.airtime-selection';

     public array $data = [];

    public function mount(): void
    {
        $this->data = AirtimeSelectionModel::first()?->only([
            'mtn_vtu',
            'glo_vtu',
            'airtel_vtu',
            'mobile_vtu',
            'mtn_share',
            'glo_share',
            'airtel_share',
            'mobile_share',
        ]) ?? [];
    }

     public function form(Form $form): Form
    {
        $providers = [
            'quickly'      => 'QuicklySim',
            'simhosting'     => 'Sim Hosting',
            'simserver'      => 'Sim Server',
            'smeplug'        => 'SME Plug',
            'msplug'         => 'MS Plug',
            'ogdamns'        => 'OG DAMNS',
            'vtpass'         => 'VTPass',
            // 'sms'            => 'SMS',
            'gmail'          => 'Gmail',
            'easyaccess'     => 'Easy Access',
            'megasub'        => 'Megasub',
            'megasubcloud'   => '101 Terabyte',
            // MSORG options
            'msorg1'         => 'MSORG 1',
            'msorg2'         => 'MSORG 2',
            'msorg3'         => 'MSORG 3',
            'msorg4'         => 'MSORG 4',
            'msorg5'         => 'MSORG 5',
            // VIRUS options
            'virus1'         => 'VIRUS 1',
            'virus2'         => 'VIRUS 2',
            'virus3'         => 'VIRUS 3',
            'virus4'         => 'VIRUS 4',
            'virus5'         => 'VIRUS 5',
        ];

        $adexProviders = AdexAPI::all()->mapWithKeys(fn($api) => [
            "adex{$api->id}" => "Adex #{$api->id} - {$api->website_url}"
        ])->toArray();

        $providers = array_merge($providers, $adexProviders);

        return $form->schema([
            Section::make('Airtime Selection Settings')->schema([
                Grid::make(2)->schema([
                    Select::make('mtn_vtu')->label('MTN VTU')->options($providers)->searchable()->required(),
                    Select::make('glo_vtu')->label('GLO VTU')->options($providers)->searchable()->required(),
                    Select::make('airtel_vtu')->label('Airtel VTU')->options($providers)->searchable()->required(),
                    Select::make('mobile_vtu')->label('9mobile VTU')->options($providers)->searchable()->required(),

                    Select::make('mtn_share')->label('MTN Share and Sell')->options($providers)->searchable()->required(),
                    Select::make('glo_share')->label('GLO Share and Sell')->options($providers)->searchable()->required(),
                    Select::make('airtel_share')->label('Airtel Share and Sell')->options($providers)->searchable()->required(),
                    Select::make('mobile_share')->label('9mobile Share and Sell')->options($providers)->searchable()->required(),
                ]),
            ]),
        ])->statePath('data');
    }

    protected function getFormActions(): Action
    {
        return Action::make('save_data_selection')
            ->label('Save')
            ->action('save');
    }

      public function save(): void
    {
        $data = $this->form->getState();

        AirtimeSelectionModel::updateOrCreate([], $data);
        
        \Filament\Notifications\Notification::make()
            ->title('Airtime vending selection saved successfully!')
            ->success()
            ->send();
    }
}
