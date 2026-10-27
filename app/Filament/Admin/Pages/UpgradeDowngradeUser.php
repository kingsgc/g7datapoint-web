<?php

namespace App\Filament\Admin\Pages;

use App\Http\Controllers\MailController;
use App\Models\User;
use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use App\Mail\UserRoleChangedNotification;
use App\Models\History;
use App\Models\Notif;
use Filament\Support\Enums\ActionSize;
use Illuminate\Support\Facades\DB;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
class UpgradeDowngradeUser extends Page
{
      use HasPageShield;
    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $navigationGroup = 'User Management';
    protected static ?string $navigationLabel = 'Role Management';
    protected static ?int $navigationSort = 9;
    protected static string $view = 'filament.admin.pages.upgrade-downgrade-user';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('User Role Management')
                    ->description('Elevate or modify user access levels')
                    ->icon('heroicon-o-user-plus')
                    ->columns(2)
                    ->schema([
                        Select::make('user_id')
                            ->label('Select User')
                            ->placeholder('Search for a user...')
                            ->options(User::query()->pluck('username', 'id'))
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
                            ->columnSpanFull()
                            ->live()
                            ->afterStateUpdated(function ($state) {
                                if ($user = User::find($state)) {
                                    $this->form->fill([
                                        'type' => $user->type,
                                        'is_active' => false,
                                        'user_id' => $user->id
                                    ]);
                                }
                            }),
                        Select::make('type')
                            ->label('New Role')
                            ->options([
                                'SMART' => 'Smart User',
                                'AWUF' => 'Awuf User',
                                'AGENT' => 'Agent',
                                'SPECIAL' => 'Special User',
                                'API' => 'API User',
                                'CUSTOMER' => 'Customer Care',
                                'ADMIN' => 'ADMIN User',
                            ])
                            ->required()
                            ->native(false)
                            ->columnSpan(1),
                        Toggle::make('is_active')
                            ->label('Send Notification Email')
                            ->helperText('User will receive an email about this change')
                            ->default(false)
                            ->onColor('success')
                            ->offColor('danger')
                            ->columnSpan(1),
                    ]),
                Actions::make([
                    Action::make('update_role')
                        ->label('Update User Role')
                        ->action(function () {
                            $this->updateRole();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Confirm Role Update')
                        ->modalDescription('Are You Sure you want to Update this User Role')
                        ->modalIcon('heroicon-o-shield-check')
                        ->modalIconColor('primary')
                        ->icon('heroicon-o-check-circle')
                        ->size(ActionSize::Large)
                        ->color('primary')
                        ->button(),
                    Action::make('reset_form')
                        ->label('Reset Form')
                        ->action(function () {
                            $this->form->fill();
                            Notification::make()
                                ->title('Form reset successfully')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Confirm Form Reset')
                        ->modalDescription('Are you sure you want to clear all form fields? This action cannot be undone.')
                        ->modalIcon('heroicon-o-exclamation-triangle')
                        ->modalIconColor('danger')
                        ->icon('heroicon-o-arrow-path')
                        ->color('gray')
                        ->outlined(),
                ]),
            ])
            ->statePath('data');
    }

    private function getConfirmationMessage(): string
    {
        $data = $this->form->getState();

        if (empty($data['user_id'])) {
            return 'Please select a user first';
        }

        $user = User::find($data['user_id']);
        $notify = $data['is_active'] ?? false ? 'and notify the user via email' : 'without email notification';

        return $user
            ? "You are about to change {$user->name}'s role from {$user->type} to {$data['type']} {$notify}. Continue?"
            : 'Selected user not found';
    }

    public function updateRole(): void
    {
        try {
            $data = $this->form->getState();
            $user = User::findOrFail($data['user_id']);

            DB::beginTransaction();

            // Generate a single reference for this transaction
            $depositRef = uniqid('Upgrade_');

            // Update user role
            $oldRole = $user->type;
            $user->type = $data['type'];
            $user->save();

            // Create transaction history with the generated reference
            $this->createUpgradeHistory($user, $data['type'], $depositRef);

            // Create notification
            $this->createUserNotification($user, $data['type']);

            // Send email if enabled (using the same reference)
            if ($data['is_active']) {
                $this->sendUpgradeEmail($user, $data['type'], $depositRef);
            }

            DB::commit();

            $this->showSuccessNotification($user);
            $this->form->fill(['user_id' => $user->id]);
        } catch (\Exception $e) {
            DB::rollBack();
            Notification::make()
                ->title('Error updating role')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function createUpgradeHistory(User $user, string $newRole, string $depositRef): void
    {
        History::create([
            'username'    => $user->username,
            'user_id'     => $user->id,
            'amount'      => 0,
            'message'     => "Your account has been upgraded to {$newRole} package",
            'oldbal'      => $user->bal,
            'newbal'      => $user->bal,
            'adex_date'   => now(),
            'plan_status' => 1,
            'transid'     => $depositRef,
            'role'        => 'upgrade',
        ]);
    }

    protected function createUserNotification(User $user, string $newRole): void
    {
        Notif::create([
            'username' => $user->username,
            'user_id'  => $user->id,
            'title'    => 'Account Upgraded',
            'message'  => "Your account has been upgraded to {$newRole} by admin.",
            'date'     => now(),
            'adex'     => 0,
        ]);
    }
    protected function showSuccessNotification(User $user): void
    {
        Notification::make()
            ->title('Role updated successfully')
            ->body("{$user->name} is now a {$user->type}")
            ->success()
            ->send();
    }

    protected function sendUpgradeEmail(User $user, string $newRole, string $depositRef): void
    {
        $emailData = [
            'name'         => $user->name,
            'email'       => $user->email,
            'username'    => $user->username,
            'title'       => 'Account Upgrade/Downgrade',
            'sender_mail' => config('app.email'),
            'app_name'    => config('app.name'),
            'amount'      => 0.00,
            'oldbal'      => number_format($user->bal, 2),
            'newbal'      => number_format($user->bal, 2),
            'deposit_type' => 'upgrade',
            'transid'     => $depositRef,
            'role'       => $newRole
        ];
        dispatch(function () use ($emailData) {
            MailController::send_mail($emailData, 'email.upgrade');
        })->afterCommit();
    }
}
