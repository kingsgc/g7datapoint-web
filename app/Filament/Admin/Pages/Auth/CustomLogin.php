<?php

namespace App\Filament\Admin\Pages\Auth;

use App\Models\User;
use Filament\Forms\Components\TextInput;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Forms\Form;
use Filament\Pages\Auth\Login as BaseLogin;
use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;

class CustomLogin extends BaseLogin
{
    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $seconds  = $exception->secondsUntilAvailable;
            throw ValidationException::withMessages([
                'data.login' => __('Too many attempts. Please try again in :seconds seconds', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ]),
            ]);
            return null;
        }
        $data = $this->form->getState();
        $field = filter_var($data['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $user = User::where($field, $data['login'])->first();
        if (!$user) {
            throw ValidationException::withMessages([
                'data.login' => __('The provided credentials do not match our records'),
            ]);
        }
        if ($user->type !== 'ADMIN') {
            throw ValidationException::withMessages([
                'data.login' => __('Unauthorized: Administrator access required'),
            ]);
        }
        if ($user->status != 1) {
            throw ValidationException::withMessages([
                'data.login' => __('Account inactive: Please contact system administrator')
            ]);
        }
        if (!Filament::auth()->attempt([
            $field => $data['login'],
            'password' => $data['password'],
            'status' => 1,
            'type' => 'ADMIN',
        ], $data['remember'] ?? false)) {
            throw ValidationException::withMessages([
                'data.login' => __('Incorrect password: Please try again'),
            ]);
            return null;
        }
        return app(LoginResponse::class);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('login')
                    ->label('Email or Username')
                    ->required()
                    ->validationMessages([
                        'required' => 'Email or username is required',
                    ])
                    ->autocomplete(),

                $this->getPasswordFormComponent()->validationMessages([
                    'required' => 'Password is required',
                ]),

                $this->getRememberFormComponent(),
            ]);
    }
}
