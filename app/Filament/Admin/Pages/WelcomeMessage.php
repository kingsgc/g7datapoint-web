<?php

namespace App\Filament\Admin\Pages;

use App\Models\Setting;
use Filament\Pages\Page;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
class WelcomeMessage extends Page
{
      use HasPageShield;
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-bottom-center';
    protected static ?string $navigationGroup = 'General Settings';

    protected static string $view = 'filament.admin.pages.welcome-message';

    public $message;
    public $is_active;

    public function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Welcome Message Configuration')
                ->schema([
                    Card::make()
                        ->schema([
                            RichEditor::make('message')
                                ->label('')
                                ->disableToolbarButtons([
                                    'attachFiles',
                                    'codeBlock',
                                ])
                                ->required()
                                ->columnSpanFull(),

                            Toggle::make('is_active')
                                ->label('Enable Welcome Message')
                                ->onColor('success')
                                ->offColor('danger'),
                        ])
                ])
        ]);
    }

    public function mount(): void
    {
        $setting =  Setting::first();
        if ($setting) {
            $this->form->fill([
                'message' => $setting->notif_message,
                'is_active' => $setting->notif_show,
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
        Setting::updateOrCreate([], [
            'notif_message' => $data['message'],
            'notif_show' => $data['is_active']
        ]);
        Notification::make()
            ->title('Success')
            ->body('Welcome Message  updated successfully.')
            ->success()
            ->send();
    }
}
