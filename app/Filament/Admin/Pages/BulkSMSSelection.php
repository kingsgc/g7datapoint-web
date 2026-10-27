<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use App\Models\BulkSmsSelection as BulkSmsSelectionModel;
use App\Models\AdexAPI;
use Filament\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
class BulkSMSSelection extends Page
{
    use HasPageShield;
    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';
    protected static string $view = 'filament.admin.pages.bulk-s-m-s-selection';
    protected static ?string $navigationGroup = "Vending";
    protected static ?string $navigationLabel = 'Bulksms';
    protected static ?int $navigationSort = 55;
    protected static ?string $modelLabel = 'Bulk SMS Vending'; 

    public array $data = [];

    public function mount(): void
    {
        $this->data = BulkSmsSelectionModel::first()?->only([
           'bulksms'
        ]) ?? [];
    }

     public function form(Form $form): Form
    {
        $providers = [
            'hollatag'    => 'Holla Tag',
        ];

        $adexProviders = AdexAPI::all()->mapWithKeys(fn($api) => [
            "adex{$api->id}" => "Adex #{$api->id} - {$api->website_url}"
        ])->toArray();

        $providers = array_merge($providers, $adexProviders);

        return $form->schema([
            Section::make('Bulksms Selection Settings')->schema([
                Grid::make(2)->schema([
                    Select::make('bulksms')->label('Bulksms')->options($providers)->searchable()->required(),
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
        BulkSmsSelectionModel::updateOrCreate([], $data);
        \Filament\Notifications\Notification::make()
            ->title('Bulksms vending selection saved successfully!')
            ->success()
            ->send();
    }
}