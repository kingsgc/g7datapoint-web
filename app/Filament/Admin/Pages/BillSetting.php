<?php

namespace App\Filament\Admin\Pages;

use App\Models\BillCharge;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use App\Models\BillSetting as ModelsBillSetting;
use Filament\Actions\Action;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
class BillSetting extends Page
{
    use HasPageShield;
    protected static ?string $navigationIcon = 'heroicon-o-light-bulb';
    protected static ?string $navigationLabel = 'Bill Commission Settings';
    protected static ?string $navigationGroup = 'Discount / Charges';
    protected static ?string $title = 'Bill Settings';
    protected static ?int $navigationSort = 28;
    protected static string $view = 'filament.admin.pages.bill-setting';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = BillCharge::first();
        if ($settings) {
            $this->form->fill([
                'min_amount' => $settings->bill_min,
                'max_amount' => $settings->bill_max,
                'commission_enabled' => $settings->direct,
            ]);
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Bill Configuration')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('min_amount')
                                ->label('Minimum Amount (₦)')
                                ->numeric()
                                ->required(),

                            TextInput::make('max_amount')
                                ->label('Maximum Amount (₦)')
                                ->numeric()
                                ->required(),
                        ]),
                        Toggle::make('commission_enabled')
                            ->label('Enable Commission')
                            ->helperText('Toggle this to enable or disable commission on bills.')
                            ->inline(false),
                    ])
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        BillCharge::updateOrCreate([], [
            'bill_min' => $data['min_amount'],
            'bill_max' => $data['max_amount'],
            'direct' => $data['commission_enabled'],
        ]);

        Notification::make()
            ->title('Bill Settings Updated')
            ->body('The minimum/maximum amount and commission status have been saved successfully.')
            ->success()
            ->send();
    }

    protected function getFormActions(): Action
    {

        return Action::make('Save Settings')
            ->action('save')
            ->color('primary')
            ->icon('heroicon-o-check');
    }
}
