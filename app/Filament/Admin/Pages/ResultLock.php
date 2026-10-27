<?php

namespace App\Filament\Admin\Pages;

use App\Models\CableLock;
use Filament\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Pages\Page;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
class ResultLock extends Page
{
    use HasPageShield;
    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';
    protected static ?string $navigationGroup = 'Lock / Unlock';
    protected static ?string $navigationLabel = 'Exam Lock Settings';
    protected static ?string $title = 'Exam Lock Settings';
    protected static ?int $navigationSort = 35;
    protected static string $view = 'filament.admin.pages.result-lock';

    public array $data = [];

    public function mount(): void
    {
        $setting = CableLock::first();
        if ($setting) {
            $this->form->fill([
                'waec' => (bool)$setting->waec,
                'nabteb' => (bool)$setting->nabteb,
                'neco' => (bool)$setting->neco,
            ]);
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Exam Lock Controls')
                    ->description('Enable or disable Exam.')
                    ->schema([
                        Grid::make(2)->schema([
                            Toggle::make('waec')->label('WAEC'),
                            Toggle::make('neco')->label('NECO'),
                            Toggle::make('nabteb')->label('NABTEB'),
                        ])
                    ])
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
        CableLock::updateOrCreate([], [
            'waec' => $data['waec'],
            'neco' => $data['neco'],
            'nabteb' => $data['nabteb']
        ]);
        \Filament\Notifications\Notification::make()
            ->title('Exam lock settings saved successfully!')
            ->success()
            ->send();
    }
}
