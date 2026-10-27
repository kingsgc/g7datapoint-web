<?php

namespace App\Filament\Admin\Pages;

use App\Models\AdexKey;
use Filament\Pages\Page;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Http;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Form;

class PaymentSetting extends Page
{
    use HasPageShield;
    protected static ?string $navigationIcon = 'heroicon-o-key';
    protected static ?string $navigationGroup = 'General Settings';
    protected static ?int $navigationSort = 4;
    protected static string $view = 'filament.admin.pages.payment-setting';
    protected static ?string $navigationLabel = 'Payment Key';
    protected static ?string $title = 'Payment Key Settings';
    public $x_skey, $x_akey, $x_business_id, $min, $max, $bank, $account_number, $account_name;
    public function form(Form $form): Form
    {


        $options = cache()->remember('paystack_banks_new', 3600, function () {
            $response = Http::withToken(env('PAYSTACK_SECRET_KEY'))
                ->get('https://api.paystack.co/bank');
            if ($response->successful()) {
                return collect($response->json('data'))->pluck('name', 'name');
            }
            return [];
        });
        return $form->schema([
            Section::make('Xixapay Key')->description('Details used to connect to xixapay site.')
                ->schema([
                    TextInput::make('x_skey')
                        ->label('Xixapay Secrete Key')
                        ->placeholder('Enter Xixapay Secrete Key')
                        ->password()
                        ->required(),
                    TextInput::make('x_akey')
                        ->label('Xixapay API Key')
                        ->placeholder('Enter Xixapay API Key')
                        ->password()
                        ->required(),
                    TextInput::make('x_business_id')
                        ->label('Xixapay Business ID')
                        ->placeholder('Enter Xixapay Business ID')
                        ->password()
                        ->required(),
                ])->columns(2)
                ->columnSpanFull(),
            Section::make('Minimum / Maximum Transfer')->description('Details this used for Dynamic Acccount')
                ->schema([
                    TextInput::make('min')
                        ->label('Minimum Transfer')
                        ->placeholder('Enter Minimum Transfer')
                        ->numeric()
                        ->required(),
                    TextInput::make('max')
                        ->label('Maximum Transfer')
                        ->placeholder('Enter Maximum Transfer')
                        ->numeric()
                        ->required(),
                ])->columns(2)
                ->columnSpanFull(),
            Section::make('Manual Fundimg')->description('Details used for manual funding.')
                ->schema([
                    Select::make('bank')
                        ->label('Bank')
                        ->required()
                        ->searchable()
                        ->options($options),
                    TextInput::make('account_number')
                        ->label('Account Number')
                        ->placeholder('Enter Account Number')
                        ->numeric()
                        ->required(),
                    TextInput::make('account_name')
                        ->label('Account Name')
                        ->placeholder('Enter Account Name')
                        ->required()
                ])->columns(2)
                ->columnSpanFull()
        ]);
    }
    public function mount(): void
    {
        $key = AdexKey::first();
        if ($key) {
            $this->form->fill([
                'x_skey' => $key->x_skey,
                'x_akey' => $key->x_akey,
                'x_business_id' => $key->x_business_id,
                'min' => $key->min,
                'max' => $key->max,
                'bank' => $key->bank_name,
                'account_number' => $key->account_number,
                'account_name' => $key->account_name,
            ]);
        }
    }
    protected function getFormActions(): Action
    {
        return Action::make('save')
            ->label('Save')
            ->color('primary')
            ->action('submit');
    }
    public function submit(): void
    {
        $data = $this->form->getState();
        AdexKey::updateOrCreate([], [
            'x_skey' => $data['x_skey'],
            'x_akey' => $data['x_akey'],
            'x_business_id' => $data['x_business_id'],
            'min' => $data['min'],
            'max' => $data['max'],
            'bank_name' => $data['bank'],
            'account_number' => $data['account_number'],
            'account_name' => $data['account_name']
        ]);
        Notification::make()
            ->title('Success')
            ->body(' updated successfully.')
            ->success()
            ->send();
    }
}
