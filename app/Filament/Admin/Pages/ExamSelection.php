<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use App\Models\ExamSelection as ExamSelectionModel;
use App\Models\AdexAPI;
use Filament\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
class ExamSelection extends Page
{
     use HasPageShield;
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static string $view = 'filament.admin.pages.exam-selection';
    protected static ?string $navigationGroup = "Vending";
    protected static ?string $navigationLabel = 'Exam';
    protected static ?int $navigationSort = 54;
    protected static ?string $modelLabel = 'Exam Vending';
    
    public array $data = [];

    public function mount(): void
    {
        $this->data = ExamSelectionModel::first()?->only([
           'waec', 'neco', 'nabteb'
        ]) ?? [];
    }

     public function form(Form $form): Form
    {
        $providers = [
            'quickly'      => 'QuicklySim',
            'self'    => 'Self Vending',
            'vtpass'         => 'VTPass',
            'easyaccess'    => 'EasyAccess (easyacccess.com.ng)'
        ];

        $adexProviders = AdexAPI::all()->mapWithKeys(fn($api) => [
            "adex{$api->id}" => "Adex #{$api->id} - {$api->website_url}"
        ])->toArray();

        $providers = array_merge($providers, $adexProviders);

        return $form->schema([
            Section::make('Exam Selection Settings')->schema([
                Grid::make(2)->schema([
                    Select::make('waec')->label('Waec Vending')->options($providers)->searchable()->required(),
                    Select::make('neco')->label('Neco Vending')->options($providers)->searchable()->required(),
                    Select::make('nabteb')->label('Nabteb Vending')->options($providers)->searchable()->required(),
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
        ExamSelectionModel::updateOrCreate([], $data);
        \Filament\Notifications\Notification::make()
            ->title('Exam vending selection saved successfully!')
            ->success()
            ->send();
    }
}
