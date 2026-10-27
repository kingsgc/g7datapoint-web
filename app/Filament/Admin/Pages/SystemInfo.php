<?php

namespace App\Filament\Admin\Pages;

use App\Models\General;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Form;
class SystemInfo extends Page
{
    use HasPageShield;
    protected static ?string $navigationIcon = 'heroicon-o-cog';
    protected static string $view = 'filament.admin.pages.system-info';
    protected static ?string $navigationGroup = 'General Settings';

    public $phone, $email, $address, $facebook, $instagram, $tiktok, $app_name, $whatsapp_group, $whatsapp_number,  $logo;

      public function form(Form $form): Form
    {
        return $form->schema([
            Section::make('System Contact Information')
                ->description('Details used for system-wide notifications, footers, and contact sections.')
                ->schema([
                    TextInput::make('app_name')
                        ->label('APP NAme')
                        ->placeholder('Enter system app name')
                        ->required(),
                    FileUpload::make('logo')
                        ->label('App Logo')
                        ->image()
                        ->disk('public')
                        ->directory('logos')
                        ->imagePreviewHeight('100')
                        ->maxSize(5120)
                        ->rules([
                            'image',
                            'max:5120',
                        ])
                        ->hint('Max 5MB (PNG, JPG)')
                        ->visibility('public')
                        ->preserveFilenames()
                        ->acceptedFileTypes(['image/png', 'image/jpeg']),
                    TextInput::make('phone')
                        ->label('Phone Number')
                        ->placeholder('Enter system support phone number')
                        ->tel()
                        ->required(),

                    TextInput::make('email')
                        ->label('Email Address')
                        ->placeholder('Enter support email address')
                        ->email()
                        ->required(),

                    TextInput::make('address')
                        ->label('Office Address')
                        ->placeholder('Enter office or business address')
                        ->required(),
                ])
                ->columns(2)
                ->columnSpanFull(),

            Section::make('Social Media Links')
                ->description('Links displayed on public pages or in communications.')
                ->schema([
                    TextInput::make('whatsapp_group')
                        ->label('Whatsapp Group')
                        ->placeholder('https://wa.me/...')
                        ->url(),
                    TextInput::make('whatsapp_number')
                        ->label('Whatsapp Number')
                        ->placeholder('https://wa.me/...')
                        ->tel(),
                    TextInput::make('facebook')
                        ->label('Facebook URL')
                        ->placeholder('https://facebook.com/yourpage')
                        ->url(),

                    TextInput::make('instagram')
                        ->label('Instagram URL')
                        ->placeholder('https://instagram.com/yourprofile')
                        ->url(),

                    TextInput::make('tiktok')
                        ->label('TikTok URL')
                        ->placeholder('https://tiktok.com/@yourhandle')
                        ->url(),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }

    public function mount(): void
    {
        $general = General::first();
        if ($general) {
            $this->form->fill([
                'app_name' => $general->app_name,
                'logo' => $general->app_logo,
                'phone' => $general->app_phone,
                'email' => $general->app_email,
                'address' => $general->app_address,
                'facebook' => $general->facebook,
                'instagram' => $general->instagram,
                'tiktok' => $general->tiktok,
                'whatsapp_group' => $general->app_group,
                'whatsapp_number' => $general->app_whatsapp,
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
        General::updateOrCreate([], [
            'app_name' => $data['app_name'],
            'app_logo' => $data['logo'],
            'app_image' => $data['logo'],
            'app_phone' => $data['phone'],
            'app_email' => $data['email'],
            'app_address' => $data['address'],
            'facebook' => $data['facebook'],
            'instagram' => $data['instagram'],
            'tiktok' => $data['tiktok'],
            'app_group' => $data['whatsapp_group'],
            'app_whatsapp' => $data['whatsapp_number'],
        ]);
        Notification::make()
            ->title('Success')
            ->body('Contact information updated successfully.')
            ->success()
            ->send();
    }
}
