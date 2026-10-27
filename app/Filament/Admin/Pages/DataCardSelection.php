<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use App\Models\DataCardSelection as DataCardSelectionModel;
use App\Models\AdexAPI;
use Filament\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
class DataCardSelection extends Page
{
     use HasPageShield;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static string $view = 'filament.admin.pages.data-card-selection';
    protected static ?string $navigationGroup = "Vending";
    protected static ?string $navigationLabel = 'Data Card';
    protected static ?int $navigationSort = 56;
    protected static ?string $modelLabel = 'Data Card Vending';

     public array $data = []; 

    public function mount(): void
    {
        $this->data = DataCardSelectionModel::first()?->only([
           'mtn','airtel', 'mobile', 'glo'
        ]) ?? [];
    }

     public function form(Form $form): Form
    {
        $providers = [
            'self' => 'Self Vending'
        ];

        $adexProviders = AdexAPI::all()->mapWithKeys(fn($api) => [
            "adex{$api->id}" => "Adex #{$api->id} - {$api->website_url}"
        ])->toArray();

        $providers = array_merge($providers, $adexProviders);

        return $form->schema([
            Section::make('Data Card Selection Settings')->schema([
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
        DataCardSelectionModel::updateOrCreate([], $data);
        \Filament\Notifications\Notification::make()
            ->title('Data Card vending selection saved successfully!')
            ->success()
            ->send();
    }
}
