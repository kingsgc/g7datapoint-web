<?php

namespace App\Filament\Admin\Pages;

use App\Http\Controllers\MailController;
use App\Models\User;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
class RestUserPassword extends Page
{
      use HasPageShield;
    protected static ?string $navigationIcon = 'heroicon-o-key';
    protected static ?string $navigationGroup = 'User Management';
    protected static ?string $navigationLabel = 'Reset Passwords';
    protected static ?int $navigationSort = 10;
    protected static string $view = 'filament.admin.pages.rest-user-password';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Password Reset')
                    ->description('Reset passwords for user accounts')
                    ->icon('heroicon-o-lock-closed')
                    ->schema([
                        Select::make('user_id')
                            ->label('Select User')
                            ->options(User::query()->pluck('username', 'id'))
                            ->getOptionLabelFromRecordUsing(fn(User $record) => "{$record->name} ({$record->email})")
                            ->searchable()
                            ->getOptionLabelUsing(fn($value): ?string => User::find($value)?->username)
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
                            ->preload()
                            ->required()
                            ->live()
                            ->columnSpanFull(),
                        TextInput::make('new_password')
                            ->label('New Password')
                            ->password()
                            ->revealable()
                            ->required()->rules([
                                'min:8',
                                'regex:/[a-z]/',
                                'regex:/[A-Z]/',
                                'regex:/[0-9]/',
                                'regex:/[@$!%*#?&]/',
                            ])
                            ->validationMessages([
                                'regex' => 'The :attribute must contain at least one uppercase letter, one lowercase letter, one number, and one special character.',
                            ])
                            ->columnSpan(1),
                        TextInput::make('confirm_password')
                            ->label('Confirm Password')
                            ->password()
                            ->revealable()
                            ->required()
                            ->same('new_password')
                            ->dehydrated(false)
                            ->columnSpan(1),
                        Toggle::make('notify_user')
                            ->label('Notify user via email')
                            ->default(false)
                            ->onColor('success')
                            ->offColor('danger')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): Action
    {

        return  Action::make('reset')
            ->label('Reset Password')
            ->submit('resetPassword')
            ->icon('heroicon-o-key')
            ->color('primary')
            ->size('lg');
    }

    public function resetPassword(): void
    {
        $data = $this->form->getState();

        $user = User::find($data['user_id']);

        if (!$user) {
            Notification::make()
                ->title('User not found')
                ->danger()
                ->send();
            return;
        }
        $user->update([
            'password' => Hash::make($data['new_password']),
        ]);
        if ($data['notify_user'] ?? false) {
            $emailData = [
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'title' => 'Password Reset',
                'sender_mail' => config('app.email'),
                'app_name' => config('app.name'),
                'password' => $data['new_password'],
                'username' => $user->username
            ];
            dispatch(function () use ($emailData) {
                MailController::send_mail($emailData, 'email.admin_reset');
            })->afterCommit();
        }
        Notification::make()
            ->title('Password reset successfully')
            ->body("Password for {$user->username} has been updated")
            ->success()
            ->send();
        $this->form->fill();
    }
}
