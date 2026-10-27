<?php

namespace App\Filament\Admin\Pages;

use App\Models\General;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class SiteSettings extends Page
{
    use HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';
    protected static ?string $navigationGroup = 'General Settings';
    protected static ?string $navigationLabel = 'Site Settings';
    protected static ?int $navigationSort = 11;
    protected static string $view = 'filament.admin.pages.site-settings';

    public array $data = [];

    public function mount(): void
    {
        $general = General::first();
        if ($general) {
            $this->data = $general->only([
                'app_name',
                'app_logo',
                'app_image',
                'app_phone',
                'app_email',
                'app_address',
                'facebook',
                'instagram',
                'tiktok',
                'app_group',
                'app_whatsapp',
            ]) ?? [];
        }
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Site Information')
                ->description('The basics shown around your storefront and in the platform itself.')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('app_name')
                            ->label('Site Name')
                            ->placeholder('e.g. G7DataPoint')
                            ->required()
                            ->maxLength(191),
                        TextInput::make('app_phone')
                            ->label('Support Phone')
                            ->placeholder('e.g. 0813 000 0000')
                            ->tel()
                            ->required()
                            ->maxLength(191),
                        TextInput::make('app_email')
                            ->label('Support Email')
                            ->placeholder('e.g. support@g7datapoint.com')
                            ->email()
                            ->required()
                            ->maxLength(191),
                        TextInput::make('app_address')
                            ->label('Head Office')
                            ->placeholder('City, State')
                            ->required()
                            ->maxLength(191),
                        TextInput::make('app_group')
                            ->label('WhatsApp Group')
                            ->placeholder('https://chat.whatsapp.com/...')
                            ->url(),
                        TextInput::make('app_whatsapp')
                            ->label('WhatsApp Number')
                            ->placeholder('e.g. 0813 000 0000')
                            ->tel(),
                    ]),
                ])->columnSpanFull(),
        ])->statePath('data');
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

        General::updateOrCreate([], [
            'app_name'       => $data['app_name'] ?? null,
            'app_logo'       => $data['app_logo'] ?? null,
            'app_image'      => $data['app_image'] ?? null,
            'app_phone'      => $data['app_phone'] ?? null,
            'app_email'      => $data['app_email'] ?? null,
            'app_address'    => $data['app_address'] ?? null,
            'app_group'      => $data['app_group'] ?? null,
            'app_whatsapp'   => $data['app_whatsapp'] ?? null,
        ]);

        Notification::make()
            ->title('Saved')
            ->body('Site settings updated successfully.')
            ->success()
            ->send();
    }
}
