<?php

namespace App\Filament\Admin\Pages;

use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
class OtherCharges extends Page
{
     use HasPageShield;
    protected static ?string $navigationIcon = 'heroicon-o-arrow-path-rounded-square';
    protected static ?string $navigationGroup = 'Discount / Charges';
    protected static ?string $navigationLabel = 'Other Charges';
    protected static ?string $title = 'Other Charges';
    protected static ?int $navigationSort = 31;
    protected static string $view = 'filament.admin.pages.other-charges';
    public array $data  = [];
    public function mount(): void
    {
        $settings = Setting::first();
        if ($settings) {
            $this->form->fill([
                'bulk_sms' => $settings->bulk_sms,
                'bulk_length' => $settings->bulk_length,
                'affliate_price' => $settings->affliate_price,
                'awuf_price' => $settings->awuf_price,
                'earning_min' => $settings->earning_min,
                'referral_price' => $settings->referral_price,
                'referral_text' => $settings->referral_text,
                'xixapay_charge_value' => $settings->xixapay_charge_value,
                'xixapay_charge_type' => $settings->xixapay_charge_type,
                'xixapay_charge_cap' => $settings->xixapay_charge_cap,
            ]);
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Other Charges')->schema([
                    Grid::make(2)->schema([
                        TextInput::make('bulk_sms')->label('Bulk Sms Price')->numeric()->required(),
                        TextInput::make('bulk_length')->label('Bulk Sms Length')->numeric()->required(),
                        TextInput::make('affliate_price')->label('Affiliate Price')->numeric()->prefix('₦')
                            ->suffix('NGN')->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2)->required(),
                        TextInput::make('awuf_price')->label('AWUF PRICE')->numeric()->prefix('₦')
                            ->suffix('NGN')->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2)->required(),
                        TextInput::make('earning_min')->label('Earning Mimum Transfer')->numeric()->prefix('₦')
                            ->suffix('NGN')->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2)->required(),
                        TextInput::make('referral_price')->label('Referral Price'),
                        Textarea::make('referral_text')->label('Referral Text'),
                        TextInput::make('xixapay_charge_value')
                            ->label('Xixapay Charge Value')
                            ->numeric()
                            ->prefix('₦')
                            ->suffix(fn(callable $get) => $get('xixapay_charge_type') === 'percentage' ? '%' : 'NGN')
                            ->required(),
                        Select::make('xixapay_charge_type')
                            ->label('Xixapay Charge Type')
                            ->options([
                                'flat' => 'Flat Rate',
                                'percentage' => 'Percentage',
                            ])
                            ->default('flat')
                            ->required(),

                        TextInput::make('xixapay_charge_cap')
                            ->label('Xixapay Charge Cap')
                            ->numeric()
                            ->prefix('₦')
                            ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2)
                            ->helperText('Max amount charged, even if percentage exceeds')
                            ->required(),

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

    public function save(): void
    {
        $data = $this->form->getState();
        if (
            $data['xixapay_charge_type'] === 'percentage' &&
            $data['xixapay_charge_value'] > 100
        ) {
            Notification::make()
                ->title('Percentage charge cannot be more than 100%')
                ->danger()
                ->send();
            return;
        }
        Setting::updateOrCreate(
            [],
            [
                'bulk_sms' => $data['bulk_sms'],
                'referral_price' => $data['referral_price'],
                'referral_text' => $data['referral_text'],
                'bulk_length' => $data['bulk_length'],
                'affliate_price' => $data['affliate_price'],
                'awuf_price' => $data['awuf_price'],
                'earning_min' => $data['earning_min'],
                'xixapay_charge_value' => $data['xixapay_charge_value'],
                'xixapay_charge_type' => $data['xixapay_charge_type'],
                'xixapay_charge_cap' => $data['xixapay_charge_cap'],
            ]
        );
        Notification::make()
            ->title('Settings Saved')
            ->success()
            ->send();
    }
}
