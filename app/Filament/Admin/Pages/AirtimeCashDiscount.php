<?php

namespace App\Filament\Admin\Pages;

use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use App\Models\CashDiscount;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
class AirtimeCashDiscount extends Page
{
     use HasPageShield;
    protected static ?string $navigationIcon = 'heroicon-o-gift';
    protected static string $view = 'filament.admin.pages.airtime-cash-discount';
    protected static ?string $navigationGroup = 'Discount / Charges';
    protected static ?string $navigationLabel = 'Airtime 2 Cash Settings';
    protected static ?string $title = 'Airtime 2 cash Configuration';
    protected static ?int $navigationSort = 29;

    public array $data  = [];

    public function mount(): void
    {
        $settings = CashDiscount::first();
        if ($settings) {
            $this->form->fill([
                'mtn_discount' => $settings->mtn,
                'mtn_sender' => $settings->mtn_number,
                'airtel_discount' => $settings->airtel,
                'airtel_sender' => $settings->airtel_number,
                'glo_discount' => $settings->glo,
                'glo_sender' => $settings->glo_number,
                'mobile_discount' => $settings->mobile,
                'mobile_sender' => $settings->mobile_number,
            ]);
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Airtime Cash Discount Settings')->schema([
                    Grid::make(2)->schema([
                        TextInput::make('mtn_discount')->label('MTN Discount (%)')->numeric()->minValue(0)
                            ->maxValue(100)->required(),
                        TextInput::make('mtn_sender')->label('MTN Sender Number')->tel()->required(),

                        TextInput::make('airtel_discount')->label('Airtel Discount (%)')->numeric()->minValue(0)
                            ->maxValue(100)->required(),
                        TextInput::make('airtel_sender')->label('Airtel Sender Number')->tel()->required(),

                        TextInput::make('glo_discount')->label('GLO Discount (%)')->numeric()->minValue(0)
                            ->maxValue(100)->required(),
                        TextInput::make('glo_sender')->label('GLO Sender Number')->tel()->required(),

                        TextInput::make('mobile_discount')->label('9mobile Discount (%)')->numeric()->minValue(0)
                            ->maxValue(100)->required(),
                        TextInput::make('mobile_sender')->label('9mobile Sender Number')->tel()->required(),
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
        CashDiscount::updateOrCreate(
            [],
            [
                'mtn' => $data['mtn_discount'],
                'mtn_number' => $data['mtn_sender'],
                'airtel' => $data['airtel_discount'],
                'airtel_number' => $data['airtel_sender'],
                'glo' => $data['glo_discount'],
                'glo_number' => $data['glo_sender'],
                'mobile' => $data['mobile_discount'],
                'mobile_number' => $data['mobile_sender'],
            ]
        );
        Notification::make()
            ->title('Saved')
            ->body('Settings saved successfully.')
            ->success()->send();
    }
}
