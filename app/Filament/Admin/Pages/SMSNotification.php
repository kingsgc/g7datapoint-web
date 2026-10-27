<?php

namespace App\Filament\Admin\Pages;

use App\Jobs\SMSNotification as JobsSMSNotification;
use Filament\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\RichEditor;
use Filament\Notifications\Notification;
use App\Models\User;
use Filament\Forms\Components\View;
use Filament\Forms\Form;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
class SMSNotification extends Page
{
    use HasPageShield;
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-bottom-center';
    protected static ?string $navigationGroup = 'Notifications';
    protected static ?string $navigationLabel = 'SMS';
    protected static ?int $navigationSort = 6;
    protected static string $view = 'filament.admin.pages.s-m-s-notification';
    public $user_group, $selected_users, $title_a, $message;
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Audience')
                    ->description('Choose who you want to notify')
                    ->schema([
                        Select::make('user_group')
                            ->label('User Group')
                            ->options([
                                'all' => 'All Users',
                                'smart' => 'Smart Users',
                                'awuf' => 'Awuf Users',
                                'api' => 'API Users',
                                'special' => 'Special Users',
                                'custom' => 'Custom Users',
                            ])
                            ->default('all')
                            ->required()
                            ->reactive(),
                        Select::make('selected_users')
                            ->label('Select Custom Users')
                            ->options(User::pluck('username', 'id'))
                            ->multiple()
                            ->searchable()
                            ->getOptionLabelUsing(fn($value): ?string => User::find($value)?->name)
                            ->getSearchResultsUsing(function (string $search): array {
                                $results = User::query()
                                    ->where(function ($query) use ($search) {
                                        $query->where('name', 'like', "%{$search}%")
                                            ->orWhere('phone', 'like', "%{$search}%")
                                            ->orWhere('type', 'like', "%{$search}%")
                                            ->orWhere('palmpay', 'like', "%{$search}%")
                                            ->orWhere('email', 'like', "%{$search}%")
                                            ->orWhere('username', 'like', "%{$search}%")
                                            ->orWhere('pin', 'like', "%{$search}%");
                                    })
                                    ->limit(50)
                                    ->pluck('username', 'id');

                                return $results->isNotEmpty() ? $results->toArray() : [];
                            })
                            ->preload()->required()
                            ->visible(fn($get) => $get('user_group') === 'custom'),
                    ]),
                Section::make('Notification Details')
                    ->description('Compose your sms notification')
                    ->schema([
                        TextInput::make('title_a')
                            ->label('Sender')
                            ->required(),
                        RichEditor::make('message')
                            ->label('Write something awesome...')
                            ->disableToolbarButtons([
                                'attachFiles',
                                'codeBlock',
                            ])
                            ->required()
                            ->columnSpanFull(),
                    ]),
                Section::make('About Dynamic Tags')
                    ->description('These tags will be replaced with user-specific data when sending sms.')
                    ->schema([
                        View::make('admin.partials.dynamic-tags-info'),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
    protected function getFormActions(): Action
    {
        return Action::make('save')
            ->label('Save')
            ->color('primary')
            ->action('sendNotification');
    }
    public function sendNotification(): void
    {

        $data = $this->form->getState();
        $group = $data['user_group'];
        $title = $data['title_a'];
        $message = $data['message'];
        $query = match ($group) {
            'all' => User::query(),
            'smart' => User::where('type', 'SMART'),
            'awuf' => User::where('type', 'AWUF'),
            'api' => User::where('type', 'API'),
            'special' => User::where('type', 'SPECIAL'),
            'custom' => User::whereIn('id', $data['selected_users'] ?? []),
            default => User::whereNull('id'),
        };
        $query->select('id')->chunk(1000, function ($users) use ($title, $message) {
            $emails = $users->pluck('id')->filter()->toArray();
            dispatch(new JobsSMSNotification($emails, $title, $message));
        });
        Notification::make()
            ->title('Notification Queued')
            ->body('sms are being sent in the background.')
            ->success()
            ->send();
        $this->form->fill();
    }
}
