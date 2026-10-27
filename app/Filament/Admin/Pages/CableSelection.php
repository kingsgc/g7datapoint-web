<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use App\Models\CableSelection as CableSelectionModel;
use App\Models\AdexAPI;
use Filament\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
class CableSelection extends Page
{
    use HasPageShield;
    protected static ?string $navigationIcon = 'heroicon-o-tv';

    protected static string $view = 'filament.admin.pages.cable-selection';
    protected static ?string $navigationGroup = "Vending";
    protected static ?string $navigationLabel = 'Cable';
    protected static ?int $navigationSort = 52;
    protected static ?string $modelLabel = 'Cable Vending'; 

     public array $data = [];

    public function mount(): void
    {
        $this->data = CableSelectionModel::first()?->only([
           'dstv',
           'gotv',
            'startime',
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
            Section::make('Cable Selection Settings')->schema([
                Grid::make(2)->schema([
                    Select::make('dstv')->label('DSTV')->options($providers)->searchable()->required(),
                    Select::make('gotv')->label('GOTV')->options($providers)->searchable()->required(),
                    Select::make('startime')->label('STARTIME')->options($providers)->searchable()->required(),
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
        CableSelectionModel::updateOrCreate([], $data);
        \Filament\Notifications\Notification::make()
            ->title('Cable vending selection saved successfully!')
            ->success()
            ->send();
    }
}
