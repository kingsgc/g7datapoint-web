<?php

namespace App\Filament\Admin\Pages;

use App\Models\CableCharge;
use Filament\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
class CableCharges extends Page
{
    use HasPageShield;
    protected static ?string $navigationIcon = 'heroicon-o-tv';
    protected static ?string $navigationGroup = 'Discount / Charges';
    protected static ?string $navigationLabel = 'Cable Commission Settings';
    protected static ?string $title = 'Cable Commission Configuration';
    protected static ?int $navigationSort = 27;
    protected static string $view = 'filament.admin.pages.cable-charges';

    public array $data = [];

    public function mount()
    {
        $commission = CableCharge::first();
        if ($commission) {
            $this->form->fill([
                'dstv_commission' => $commission->dstv,
                'gotv_commission' => $commission->gotv,
                'startimes_commission' => $commission->startime,
                'enable_commission' => $commission->direct
            ]);
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Commission Settings')
                    ->description('Set the commission rates (%) for cable subscriptions.')
                    ->schema([
                        Toggle::make('enable_commission')
                            ->label('Enable Commission')
                            ->helperText('Turn on to deduct commission from each cable transaction.')
                            ->default(true),
                        Grid::make(2)->schema([
                            TextInput::make('dstv_commission')
                                ->label('DSTV Commission (%)')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(100)
                                ->required(),
                            TextInput::make('gotv_commission')
                                ->label('GOTV Commission (%)')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(100)
                                ->required(),
                            TextInput::make('startimes_commission')
                                ->label('Startimes Commission (%)')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(100)
                                ->required(),
                        ]),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): Action
    {
        return
            Action::make('submit_commission')
            ->label('Save Commission Settings')
            ->color('primary')
            ->icon('heroicon-o-check-circle')
            ->action('save')
        ;
    }

    public function save()
    {
        $data = $this->form->getState();
        CableCharge::updateOrCreate([], [
            'dstv' => $data['dstv_commission'],
            'gotv' => $data['gotv_commission'],
            'startime' => $data['startimes_commission'],
            'direct' => $data['enable_commission']
        ]);
        Notification::make()
            ->title('Commission Settings Saved')
            ->body('Your cable commission settings have been successfully updated.')
            ->success()
            ->send();
    }
}
