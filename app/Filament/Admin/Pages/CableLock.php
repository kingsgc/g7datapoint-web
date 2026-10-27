<?php

namespace App\Filament\Admin\Pages;

use App\Models\CableLock as ModelsCableLock;
use Filament\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Pages\Page;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
class CableLock extends Page
{
    use HasPageShield;
    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';
    protected static ?string $navigationGroup = 'Lock / Unlock';
    protected static ?string $navigationLabel = 'Cable Lock Settings';
    protected static ?string $title = 'Cable Lock Settings';
    protected static ?int $navigationSort = 34;
    protected static string $view = 'filament.admin.pages.cable-lock';

    public array $data = [];

    public function mount(): void
    {
        $setting = ModelsCableLock::first();
        if ($setting) {
            $this->form->fill([
                'gotv' => (bool)$setting->gotv,
                'dstv' => (bool)$setting->dstv,
                'startime' => (bool)$setting->startime,
            ]);
        }
    }
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Cable Lock Controls')
                    ->description('Enable or disable cable.')
                    ->schema([
                        Grid::make(2)->schema([
                            Toggle::make('gotv')->label('GOTV'),
                            Toggle::make('startime')->label('STARTIME'),
                            Toggle::make('dstv')->label('DSTV'),
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
        ModelsCableLock::updateOrCreate([], [
            'gotv' => $data['gotv'],
            'dstv' => $data['dstv'],
            'startime' => $data['startime']
        ]);
        \Filament\Notifications\Notification::make()
            ->title('cable lock settings saved successfully!')
            ->success()
            ->send();
    }
}
