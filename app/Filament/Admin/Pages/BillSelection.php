<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use App\Models\BillSelection as BillSelectionModel;
use App\Models\AdexAPI;
use Filament\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
class BillSelection extends Page
{
     use HasPageShield;
    protected static ?string $navigationIcon = 'heroicon-o-light-bulb';
    protected static string $view = 'filament.admin.pages.bill-selection';
     protected static ?string $navigationGroup = "Vending";
    protected static ?string $navigationLabel = 'Bill';
    protected static ?int $navigationSort = 53;
    protected static ?string $modelLabel = 'Bill Vending'; 

    public array $data = [];

    public function mount(): void
    {
        $this->data = BillSelectionModel::first()?->only([
           'bill',
        ]) ?? [];
    }

     public function form(Form $form): Form
    {
        $providers = [
            'quickly'      => 'QuicklySim',
            'vtpass'         => 'VTPass',
        ];

        $adexProviders = AdexAPI::all()->mapWithKeys(fn($api) => [
            "adex{$api->id}" => "Adex #{$api->id} - {$api->website_url}"
        ])->toArray();

        $providers = array_merge($providers, $adexProviders);

        return $form->schema([
            Section::make('Bill Selection Settings')->schema([
                Grid::make(2)->schema([
                    Select::make('bill')->label('Bill Vending')->options($providers)->searchable()->required(),
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
        BillSelectionModel::updateOrCreate([], $data);
        \Filament\Notifications\Notification::make()
            ->title('Bill vending selection saved successfully!')
            ->success()
            ->send();
    }
}
