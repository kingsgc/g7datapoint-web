<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use App\Models\RechargeCardSelection as RechargeCardSelectionModel;
use App\Models\AdexAPI;
use Filament\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
class RechargeCardSelection extends Page
{
    use HasPageShield;
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static string $view = 'filament.admin.pages.recharge-card-selection';
    protected static ?string $navigationGroup = "Vending";
    protected static ?string $navigationLabel = 'Recharge Card';
    protected static ?int $navigationSort = 57;
    protected static ?string $modelLabel = 'Recharge Card Vending';

     public array $data = []; 

    public function mount(): void
    {
        $this->data = RechargeCardSelectionModel::first()?->only([
           'mtn','airtel', 'mobile', 'glo'
        ]) ?? [];
    }

     public function form(Form $form): Form
    {
        $providers = [
            'self' => 'Self Vending',
            'mobilesub' => 'Mobilesub'
        ];

        $adexProviders = AdexAPI::all()->mapWithKeys(fn($api) => [
            "adex{$api->id}" => "Adex #{$api->id} - {$api->website_url}"
        ])->toArray();

        $providers = array_merge($providers, $adexProviders);

        return $form->schema([
            Section::make('Recharge Card Selection Settings')->schema([
                Grid::make(2)->schema([
                    Select::make('mtn')->label('MTN')->options($providers)->searchable()->required(),
                    Select::make('glo')->label('GLO')->options($providers)->searchable()->required(),
                    Select::make('airtel')->label('AIRTEL')->options($providers)->searchable()->required(),
                    Select::make('mobile')->label('9mobile')->options($providers)->searchable()->required(),
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
        RechargeCardSelectionModel::updateOrCreate([], $data);
        \Filament\Notifications\Notification::make()
            ->title('Recharge Card vending selection saved successfully!')
            ->success()
            ->send();
    }
}
