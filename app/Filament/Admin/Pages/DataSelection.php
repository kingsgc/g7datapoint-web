<?php

namespace App\Filament\Admin\Pages;

use App\Models\AdexAPI;
use App\Models\DataSelection as ModelsDataSelection;
use Filament\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Page;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
class DataSelection extends Page
{
     use HasPageShield;
    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';
    protected static string $view = 'filament.admin.pages.data-selection';
    protected static ?string $navigationGroup = "Vending";
    protected static ?string $navigationLabel = 'Data';
    protected static ?int $navigationSort = 51;
    protected static ?string $modelLabel = 'Data Vending'; 

    public array $data = [];

    public function mount(): void
    {
        $this->data = ModelsDataSelection::first()?->only([
            'mtn_sme',
            'glo_sme',
            'airtel_sme',
            'mobile_sme',
            'mtn_cg',
            'glo_cg',
            'airtel_cg',
            'mobile_cg',
            'mtn_g',
            'glo_g',
            'airtel_g',
            'mobile_g',
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
            Section::make('Data Selection Settings')->schema([
                Grid::make(2)->schema([
                    Select::make('mtn_sme')->label('MTN SME')->options($providers)->searchable()->required(),
                    Select::make('glo_sme')->label('GLO SME')->options($providers)->searchable()->required(),
                    Select::make('airtel_sme')->label('Airtel SME')->options($providers)->searchable()->required(),
                    Select::make('mobile_sme')->label('9mobile SME')->options($providers)->searchable()->required(),

                    Select::make('mtn_cg')->label('MTN Corporate')->options($providers)->searchable()->required(),
                    Select::make('glo_cg')->label('GLO Corporate')->options($providers)->searchable()->required(),
                    Select::make('airtel_cg')->label('Airtel Corporate')->options($providers)->searchable()->required(),
                    Select::make('mobile_cg')->label('9mobile Corporate')->options($providers)->searchable()->required(),

                    Select::make('mtn_g')->label('MTN Gifting')->options($providers)->searchable()->required(),
                    Select::make('glo_g')->label('GLO Gifting')->options($providers)->searchable()->required(),
                    Select::make('airtel_g')->label('Airtel Gifting')->options($providers)->searchable()->required(),
                    Select::make('mobile_g')->label('9mobile Gifting')->options($providers)->searchable()->required(),
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

        ModelsDataSelection::updateOrCreate([], $data);

        \Filament\Notifications\Notification::make()
            ->title('Data vending selection saved successfully!')
            ->success()
            ->send();
    }
}
