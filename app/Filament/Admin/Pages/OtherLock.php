<?php

namespace App\Filament\Admin\Pages;

use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class OtherLock extends Page
{
    use HasPageShield;
    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';
    protected static ?string $navigationGroup = 'Lock / Unlock';
    protected static ?string $title = 'Other Services Lock Settings';
    protected static ?int $navigationSort = 38;
    protected static string $view = 'filament.admin.pages.other-lock';
    public array $data = [];

    public function mount(): void
    {
        $setting = Setting::first();
        if ($setting) {
            $this->form->fill([
                'allow_pin' => (bool) $setting->allow_pin,
                'referral' => (bool) $setting->referral,
                'is_feature' => (bool) $setting->is_feature,
                'is_verify_email' => (bool) $setting->is_verify_email,
                'verify_login' => (bool) $setting->verify_login,
                'bulksms' => (bool) $setting->bulksms,
                'bill' => (bool) $setting->bill,
                'allow_limit' => (bool) $setting->allow_limit,
                'stock' => (bool) $setting->stock
            ]);
        }
    }
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Other Services Lock Controls')
                    ->description('Enable or disable.')
                    ->schema([
                        Grid::make(2)->schema([
                            Toggle::make('allow_pin')->label('Allow Transaction PIN'),
                            Toggle::make('referral')->label('Earning'),
                            Toggle::make('is_feature')->label('Feature Images'),
                            Toggle::make('is_verify_email')->label('Email Verification (on SignUP)'),
                            Toggle::make('verify_login')->label('Verify On Login'),
                            Toggle::make('bulksms')->label('BULK SMS'),
                            Toggle::make('bill')->label('Electricity Bill'),
                            Toggle::make('allow_limit')->label('Allow User Transaction Limit'),
                            Toggle::make('stock')->label('Stock Wallet Lock')
                        ]),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): Action
    {
        return Action::make('save')
            ->label('Save Settings')
            ->color('primary')
            ->action('save');
    }
    public function save()
    {
        $data = $this->form->getState();
        Setting::updateorCreate([], [
            'allow_pin' => (bool) $data['allow_pin'],
            'referral' => (bool) $data['referral'],
            'is_feature' => (bool) $data['is_feature'],
            'is_verify_email' => (bool) $data['is_verify_email'],
            'verify_login' => (bool) $data['verify_login'],
            'bulksms' => (bool) $data['bulksms'],
            'bill' => (bool) $data['bill'],
            'allow_limit' => (bool) $data['allow_limit'],
            'stock' => (bool) $data['stock']
        ]);
        Notification::make()
            ->title('Other Services lock settings saved successfully!')
            ->success()
            ->send();
    }
}
