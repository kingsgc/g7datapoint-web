<?php

namespace App\Filament\Admin\Pages;

use App\Models\AirtimeDiscount as ModelsAirtimeDiscount;
use Filament\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Forms\Components\Grid;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
class AirtimeDiscount extends Page implements HasForms
{
    use InteractsWithForms;
     use HasPageShield;
    protected static ?string $navigationIcon = 'heroicon-o-device-phone-mobile';
    protected static ?string $navigationGroup = 'Discount / Charges';
    protected static ?string $navigationLabel = 'Airtime Discount Settings';
    protected static ?string $title = 'Airtime Discount Configuration';
    protected static ?int $navigationSort = 26;
    protected static string $view = 'filament.admin.pages.airtime-discount';

    public array $data = [];

    public function mount()
    {
        $discount = ModelsAirtimeDiscount::first();
        if ($discount) {
            $this->form->fill([
                'limits' => [
                    'min' => $discount->min_airtime,
                    'max' => $discount->max_airtime
                ],
                'mtn' => [
                    'vtu_smart' => $discount->mtn_vtu_smart,
                    'vtu_awuf' => $discount->mtn_vtu_awuf,
                    'vtu_agent' => $discount->mtn_vtu_agent,
                    'vtu_api' => $discount->mtn_vtu_api,
                    'vtu_special' => $discount->mtn_vtu_special
                ],
                'airtel' => [
                    'vtu_smart' => $discount->airtel_vtu_smart,
                    'vtu_awuf' => $discount->airtel_vtu_awuf,
                    'vtu_agent' => $discount->airtel_vtu_agent,
                    'vtu_api' => $discount->airtel_vtu_api,
                    'vtu_special' => $discount->airtel_vtu_special
                ],
                'glo' => [
                    'vtu_smart' => $discount->glo_vtu_smart,
                    'vtu_awuf' => $discount->glo_vtu_awuf,
                    'vtu_agent' => $discount->glo_vtu_agent,
                    'vtu_api' => $discount->glo_vtu_api,
                    'vtu_special' => $discount->glo_vtu_special
                ],
                '9mobile' => [
                    'vtu_smart' => $discount->mobile_vtu_smart,
                    'vtu_awuf' => $discount->mobile_vtu_awuf,
                    'vtu_agent' => $discount->mobile_vtu_agent,
                    'vtu_api' => $discount->mobile_vtu_api,
                    'vtu_special' => $discount->mobile_vtu_special
                ]
            ]);
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Airtime Transfer Limits')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('limits.min')
                                ->label('Minimum Amount (₦)')
                                ->numeric()
                                ->required(),
                            TextInput::make('limits.max')
                                ->label('Maximum Amount (₦)')
                                ->numeric()
                                ->required(),
                        ])
                    ]),
                Section::make('MTN Discount Rates (%)')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('mtn.vtu_smart')
                                ->label('VTU Smart')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->maxValue(100),
                            TextInput::make('mtn.vtu_awuf')
                                ->label('VTU Awuf')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->maxValue(100),
                            TextInput::make('mtn.vtu_agent')
                                ->label('VTU Agent')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->maxValue(100),
                            TextInput::make('mtn.vtu_api')
                                ->label('VTU API')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->maxValue(100),
                            TextInput::make('mtn.vtu_special')
                                ->label('VTU Special')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->maxValue(100),
                        ])
                    ]),

                Section::make('Airtel Discount Rates (%)')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('airtel.vtu_smart')
                                ->label('VTU Smart')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->maxValue(100),
                            TextInput::make('airtel.vtu_awuf')
                                ->label('VTU Awuf')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->maxValue(100),
                            TextInput::make('airtel.vtu_agent')
                                ->label('VTU Agent')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->maxValue(100),
                            TextInput::make('airtel.vtu_api')
                                ->label('VTU API')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->maxValue(100),
                            TextInput::make('airtel.vtu_special')
                                ->label('VTU Special')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->maxValue(100),
                        ])
                    ]),

                Section::make('Glo Discount Rates (%)')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('glo.vtu_smart')
                                ->label('VTU Smart')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->maxValue(100),
                            TextInput::make('glo.vtu_awuf')
                                ->label('VTU Awuf')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->maxValue(100),
                            TextInput::make('glo.vtu_agent')
                                ->label('VTU Agent')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->maxValue(100),
                            TextInput::make('glo.vtu_api')
                                ->label('VTU API')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->maxValue(100),
                            TextInput::make('glo.vtu_special')
                                ->label('VTU Special')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->maxValue(100),
                        ])
                    ]),

                Section::make('9mobile Discount Rates (%)')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('9mobile.vtu_smart')
                                ->label('VTU Smart')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->maxValue(100),
                            TextInput::make('9mobile.vtu_awuf')
                                ->label('VTU Awuf')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->maxValue(100),
                            TextInput::make('9mobile.vtu_agent')
                                ->label('VTU Agent')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->maxValue(100),
                            TextInput::make('9mobile.vtu_api')
                                ->label('VTU API')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->maxValue(100),
                            TextInput::make('9mobile.vtu_special')
                                ->label('VTU Special')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->maxValue(100),
                        ])
                    ]),
            ])
            ->statePath('data');
    }
    protected function getFormActions(): Action
    {
        return
            Action::make('submit_a')
            ->label('Proceed')
            ->color('primary')
            ->size('lg')
            ->icon('heroicon-o-check-circle')
            ->action('save')
        ;
    }
    public function save()
    {
        $data = $this->form->getState();
        ModelsAirtimeDiscount::updateOrCreate([],[
            // Limits
            'min_airtime' => $data['limits']['min'],
            'max_airtime' => $data['limits']['max'],

            // MTN VTU
            'mtn_vtu_smart' => $data['mtn']['vtu_smart'],
            'mtn_vtu_awuf' => $data['mtn']['vtu_awuf'],
            'mtn_vtu_agent' => $data['mtn']['vtu_agent'],
            'mtn_vtu_api' => $data['mtn']['vtu_api'],
            'mtn_vtu_special' => $data['mtn']['vtu_special'],

            // Airtel VTU
            'airtel_vtu_smart' => $data['airtel']['vtu_smart'],
            'airtel_vtu_awuf' => $data['airtel']['vtu_awuf'],
            'airtel_vtu_agent' => $data['airtel']['vtu_agent'],
            'airtel_vtu_api' => $data['airtel']['vtu_api'],
            'airtel_vtu_special' => $data['airtel']['vtu_special'],

            // Glo VTU
            'glo_vtu_smart' => $data['glo']['vtu_smart'],
            'glo_vtu_awuf' => $data['glo']['vtu_awuf'],
            'glo_vtu_agent' => $data['glo']['vtu_agent'],
            'glo_vtu_api' => $data['glo']['vtu_api'],
            'glo_vtu_special' => $data['glo']['vtu_special'],

            // 9mobile VTU
            'mobile_vtu_smart' => $data['9mobile']['vtu_smart'],
            'mobile_vtu_awuf' => $data['9mobile']['vtu_awuf'],
            'mobile_vtu_agent' => $data['9mobile']['vtu_agent'],
            'mobile_vtu_api' => $data['9mobile']['vtu_api'],
            'mobile_vtu_special' => $data['9mobile']['vtu_special'],
        ]);

        Notification::make()
            ->title('Airtime Discounts Updated')
            ->body('All airtime discount settings have been saved successfully.')
            ->success()
            ->send();
    }
}
