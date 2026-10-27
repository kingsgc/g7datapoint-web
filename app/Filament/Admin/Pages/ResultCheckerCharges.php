<?php

namespace App\Filament\Admin\Pages;

use App\Models\ResultCharge;
use Filament\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
class ResultCheckerCharges extends Page
{
      use HasPageShield;
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup = 'Discount / Charges';
    protected static ?string $navigationLabel = 'Result Checker Settings';
    protected static ?string $title = 'Exam Configuration';
    protected static ?int $navigationSort = 30;
    protected static string $view = 'filament.admin.pages.result-checker-charges';
    public array $data  = [];
    public function mount(): void
    {
        $settings = ResultCharge::first();
        if ($settings) {
            $this->form->fill([
                'waec' => $settings->waec,
                'neco' => $settings->neco,
                'nabteb' => $settings->nabteb,
            ]);
        }
    }
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Exam Settings')->schema([
                    Grid::make(2)->schema([
                        TextInput::make('neco')->label('NECO')->numeric()->prefix('₦')
                            ->suffix('NGN')->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2)->required(),
                        TextInput::make('waec')->label('WAEC')->numeric()->prefix('₦')
                            ->suffix('NGN')->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2)->required(),
                        TextInput::make('nabteb')->label('NABTEB')->numeric()->prefix('₦')
                            ->suffix('NGN')->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2)->required(),
                    ])
                ])
            ])
            ->statePath('data');
    }

    protected function getFormActions(): Action
    {
        return
            Action::make('save_a')
            ->label('Save Settings')
            ->action('save');
    }
    public function save()
    {
        $data = $this->form->getState();
        ResultCharge::updateOrCreate(
            [],
            [
                'waec' => $data['waec'],
                'neco' => $data['neco'],
                'nabteb' => $data['nabteb'],
            ]
        );
        Notification::make()
            ->title('Saved')
            ->body('Settings saved successfully.')
            ->success()->send();
    }
}
